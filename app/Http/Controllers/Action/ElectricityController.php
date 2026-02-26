<?php

namespace App\Http\Controllers\Action;

use App\Helpers\RequestIdHelper;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Services1;
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
        $service = Services1::where('name', 'Electricity')->first();
        if (!$service) {
            return back()->with('error', 'Electricity service not available.');
        }

        // 2. Find the specific Disco Field
        $serviceField = \App\Models\ServiceField::where('service_id', $service->id)
            ->where('description', $request->service_id)
            ->first();

        if (!$serviceField) {
            return back()->with('error', 'Disco service not configured.');
        }

        // 3. Get Price/Markup from DB
        $markup = $serviceField->getPriceForUserType($user->role);
        $payableAmount = $amount + $markup;

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
