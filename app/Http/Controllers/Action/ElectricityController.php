<?php

namespace App\Http\Controllers\Action;

use App\Helpers\RequestIdHelper;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ElectricityController extends Controller
{
    protected $loginUserId;
    
    // API Configuration - loaded from .env
    private function getApiBaseUrl()
    {
        return env('AREWA_BASE_URL', 'https://api.arewasmart.com.ng/api/v1');
    }

    private function getApiToken()
    {
        return env('AREWA_API_TOKEN');
    }

    public function __construct()
    {
        $this->loginUserId = Auth::id();
    }

    /**
     * Ensure Electricity service and disco fields exist in database
     */
    private function ensureElectricityServiceExists()
    {
        // 1. Create or get Electricity service
        $service = \App\Models\Service::firstOrCreate(
            ['name' => 'Electricity'],
            ['is_active' => '1']
        );

        // 2. Define disco fields with their codes
        $discos = [
            ['field_name' => 'Ikeja Electric', 'description' => 'ikeja-electric', 'field_code' => 'ikeja-electric'],
            ['field_name' => 'Eko Electric', 'description' => 'eko-electric', 'field_code' => 'eko-electric'],
            ['field_name' => 'Kano Electric', 'description' => 'kano-electric', 'field_code' => 'kano-electric'],
            ['field_name' => 'Port Harcourt Electric', 'description' => 'portharcourt-electric', 'field_code' => 'portharcourt-electric'],
            ['field_name' => 'Jos Electric', 'description' => 'jos-electric', 'field_code' => 'jos-electric'],
            ['field_name' => 'Ibadan Electric', 'description' => 'ibadan-electric', 'field_code' => 'ibadan-electric'],
            ['field_name' => 'Kaduna Electric', 'description' => 'kaduna-electric', 'field_code' => 'kaduna-electric'],
            ['field_name' => 'Abuja Electric', 'description' => 'abuja-electric', 'field_code' => 'abuja-electric'],
            ['field_name' => 'Enugu Electric', 'description' => 'enugu-electric', 'field_code' => 'enugu-electric'],
            ['field_name' => 'Benin Electric', 'description' => 'benin-electric', 'field_code' => 'benin-electric'],
            ['field_name' => 'Aba Electric', 'description' => 'aba-electric-payment', 'field_code' => 'aba-electric-payment'],
            ['field_name' => 'Yola Electric', 'description' => 'yola-electric', 'field_code' => 'yola-electric'],
        ];

        // 3. Create service fields if they don't exist
        foreach ($discos as $disco) {
            \App\Models\ServiceField::firstOrCreate(
                [
                    'service_id' => $service->id,
                    'description' => $disco['description']
                ],
                [
                    'field_name' => $disco['field_name'],
                    'field_code' => $disco['field_code'],
                    'base_price' => 0,
                    'is_active' => '1',
                ]
            );
        }
    }

    /**
     * Show Electricity Purchase Page
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00, 'status' => 'active']
        );

        // Ensure Electricity service and disco fields exist
        $this->ensureElectricityServiceExists();

        // Fetch Electricity purchase history from transactions
        $history = Transaction::where('user_id', $user->id)
            ->where('description', 'LIKE', 'Electricity%')
            ->latest()
            ->paginate(10);

        return view('utilities.buy-electricity', compact('wallet', 'history', 'user'));
    }

    /**
     * Verify Meter Number
     */
    public function verifyMeter(Request $request)
    {
        $request->validate([
            'service_id'   => 'required|string',
            'meter_type'   => 'required|string|in:prepaid,postpaid',
            'meter_number' => 'required|string',
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getApiToken(),
                'Accept'        => 'application/json',
            ])->post($this->getApiBaseUrl() . '/electricity/verify', [
                'serviceID'      => $request->service_id,
                'billersCode'    => $request->meter_number,
                'variation_code' => $request->service_id . '-' . $request->meter_type,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    $content = $data['data'] ?? [];
                    $customerName = $content['customer_name'] ?? 'Unknown';
                    $address = $content['address'] ?? '';
                    
                    return response()->json([
                        'success'       => true,
                        'customer_name' => $customerName,
                        'address'       => $address,
                    ]);
                }
            }

            $errorMessage = $response->json()['message'] ?? 'Unable to verify meter number. Please check and try again.';
            return response()->json(['success' => false, 'message' => $errorMessage]);

        } catch (\Exception $e) {
            Log::error('Electricity Verification Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Verification failed due to a system error.']);
        }
    }

    /**
     * Purchase Electricity
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'service_id'   => 'required|string',
            'meter_type'   => 'required|string|in:prepaid,postpaid',
            'meter_number' => 'required|string',
            'amount'       => 'required|numeric|min:100',
            'phone'        => 'required|numeric|digits:11',
        ]);

        $user = Auth::user();
        $requestId = RequestIdHelper::generateRequestId();
        $amount = $request->amount;

        // 1. Find the Electricity Service
        $service = \App\Models\Service::where('name', 'Electricity')->first();
        if (!$service) {
            $service = \App\Models\Service::firstOrCreate(['name' => 'Electricity'], ['is_active' => '1']);
        }

        // 2. Find the specific Disco Field
        $serviceField = \App\Models\ServiceField::where('service_id', $service->id)
            ->where('description', $request->service_id)
            ->first();

        // 3. Calculate Discount/Commission (if any)
        $discountPercentage = 0;
        if ($serviceField) {
            $userType = $user->user_type ?? 'user';
            
            $servicePrice = \App\Models\ServicePrice::where('service_field_id', $serviceField->id)
                ->where('user_type', $userType)
                ->first();

            if ($servicePrice) {
                $discountPercentage = $servicePrice->price;
            } else {
                $discountPercentage = $serviceField->base_price ?? 0;
            }
        }

        $discountAmount = ($amount * $discountPercentage) / 100;
        $discoName = strtoupper(str_replace('-', ' ', $request->service_id));
        $payableAmount = $amount - $discountAmount;

        // Charge-first strategy with DB transaction
        try {
            return DB::transaction(function () use ($user, $payableAmount, $request, $requestId, $discoName, $amount, $discountAmount) {
                // Lock wallet for update to prevent race conditions
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                
                if (!$wallet || $wallet->balance < $payableAmount) {
                    throw new \Exception('Insufficient wallet balance! You need ₦' . number_format($payableAmount, 2));
                }

                // 1. Deduct funds immediately
                $wallet->decrement('balance', $payableAmount);

                try {
                    // 2. Call Arewa Smart Electricity API
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->getApiToken(),
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                    ])->post($this->getApiBaseUrl() . '/electricity/purchase', [
                        'serviceID'      => $request->service_id,
                        'billersCode'    => $request->meter_number,
                        'variation_code' => $request->service_id . '-' . $request->meter_type,
                        'amount'         => $amount,
                        'phone'          => $request->phone,
                        'request_id'     => $requestId,
                    ]);

                    if ($response->successful()) {
                        $result = $response->json();
                        
                        if (isset($result['status']) && $result['status'] === 'success') {
                            $apiData = $result['data'] ?? [];
                            $token = $apiData['token'] ?? null;
                            $transactionRef = $apiData['transaction_ref'] ?? $requestId;
                            $finalToken = $token ?? 'Electricity Payment Successful';

                            $description = "Electricity Payment - {$discoName} ({$request->meter_type}) - Meter: {$request->meter_number}";
                            if($request->meter_type == 'prepaid' && $token) {
                                $description .= " - Token: {$token}";
                            }

                            // Success: Create Transaction Record and commit
                            Transaction::create([
                                'referenceId'         => $transactionRef,
                                'user_id'             => $user->id,
                                'amount'              => $payableAmount,
                                'service_type'        => 'electricity',
                                'service_description' => $description,
                                'type'                => 'debit',
                                'status'              => 'Approved',
                                'gateway'             => 'arewa_smart',
                            ]);

                            return redirect()->route('user.thankyou')->with([
                                'success'         => 'Electricity payment successful!',
                                'transaction_ref' => $transactionRef,
                                'request_id'      => $requestId,
                                'mobile'          => $request->meter_number,
                                'amount'          => $amount,
                                'paid'            => $payableAmount,
                                'token'           => $finalToken,
                                'network'         => $discoName,
                                'type'            => 'electricity'
                            ]);
                        } else {
                            // API Failed: Rollback (via exception)
                            Log::error('Electricity API Error', ['response' => $result]);
                            $errorMessage = $result['message'] ?? 'Payment failed. Please try again.';
                            throw new \Exception($errorMessage);
                        }
                    } else {
                        // HTTP Error: Rollback
                        Log::error('Electricity HTTP Error', ['body' => $response->body()]);
                        throw new \Exception('Service unavailable.');
                    }

                } catch (\Exception $e) {
                    // API/Internal Exception: Rollback (is handled by the outer closure throwing)
                    Log::error('Electricity refactor API error: ' . $e->getMessage());
                    throw $e;
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
