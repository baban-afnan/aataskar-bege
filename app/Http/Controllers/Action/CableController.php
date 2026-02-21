<?php

namespace App\Http\Controllers\Action;

use App\Helpers\RequestIdHelper;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CableController extends Controller
{
    /**
     * Show Cable TV Purchase Page
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

        // Fetch Cable purchase history
        $history = Report::where('user_id', $user->id)
            ->where('type', 'cable')
            ->latest()
            ->paginate(10);

        return view('utilities.buy-cable', compact('wallet', 'history'));
    }

    /**
     * Fetch Variations (Plans) from DB or VTPass
     */
    public function getVariations(Request $request)
    {
        $request->validate(['service_id' => 'required|string']);
        $serviceId = $request->service_id;

        // 1. Try fetching from Database first
        $variations = DB::table('data_variations')
            ->where('service_id', $serviceId)
            ->select('variation_code as code', 'name', 'variation_amount as amount')
            ->get();

        if ($variations->isNotEmpty()) {
            return response()->json(['success' => true, 'variations' => $variations]);
        }

        // 2. If not in DB, fetch from API
        try {
            $response = Http::withHeaders([
                'api-key'    => env('API_KEY'),
                'secret-key' => env('SECRET_KEY'),
            ])->get(env('VARIATION_URL') . $serviceId);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['content']['variations'])) {
                    $variations = [];
                    foreach ($data['content']['variations'] as $v) {
                        // Prepare for response
                        $variations[] = [
                            'code'   => $v['variation_code'],
                            'name'   => $v['name'],
                            'amount' => $v['variation_amount'],
                        ];
                        
                        // Save to DB
                        DB::table('data_variations')->updateOrInsert(
                            ['variation_code' => $v['variation_code'], 'service_id' => $serviceId],
                            [
                                'name'             => $v['name'],
                                'variation_amount' => $v['variation_amount'],
                                'fixed_price'      => $v['fixedPrice'] ?? 'Yes',
                                'updated_at'       => Carbon::now(),
                            ]
                        );
                    }
                    return response()->json(['success' => true, 'variations' => $variations]);
                }
            }
            return response()->json(['success' => false, 'message' => 'Failed to fetch plans.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error fetching plans.']);
        }
    }

    /**
     * Verify Smartcard / IUC Number
     */
    public function verifyIuc(Request $request)
    {
        $request->validate([
            'service_id' => 'required|string',
            'billersCode' => 'required|string',
        ]);

        try {
            $response = Http::withHeaders([
                'api-key'    => env('API_KEY'),
                'secret-key' => env('SECRET_KEY'),
            ])->post(env('BASE_URL', 'https://sandbox.vtpass.com/api') . '/merchant-verify', [
                'serviceID'   => $request->service_id,
                'billersCode' => $request->billersCode,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['code']) && $data['code'] == '000') {
                    $content = $data['content'];
                    
                    return response()->json([
                        'success'        => true,
                        'customer_name'  => $content['Customer_Name'] ?? 'Unknown',
                        'status'         => $content['Status'] ?? 'N/A',
                        'due_date'       => $content['Due_Date'] ?? 'N/A',
                        'customer_number'=> $content['Customer_Number'] ?? $request->billersCode,
                        'current_bouquet'=> $content['Current_Bouquet'] ?? 'N/A', // Some APIs return this
                        'renewal_amount' => $content['Renewal_Amount'] ?? 0, // Important for renewal
                    ]);
                }
            }

            return response()->json(['success' => false, 'message' => 'Unable to verify IUC number.']);

        } catch (\Exception $e) {
            Log::error('Cable Verification Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Verification failed.']);
        }
    }

    /**
     * Purchase Cable Subscription
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'service_id'        => 'required|string',
            'billersCode'       => 'required|string',
            'subscription_type' => 'required|string|in:change,renew',
            'phone'             => 'required|numeric|digits:11',
            'amount'            => 'required|numeric',
            // variation_code is required if type is 'change'
            'variation_code'    => 'nullable|string',
        ]);

        $user = Auth::user();
        $requestId = RequestIdHelper::generateRequestId();
        $amount = $request->amount;

        // Charge-first strategy with DB transaction
        try {
            return DB::transaction(function () use ($user, $amount, $requestId, $request) {
                // Lock wallet for update to prevent race conditions
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                
                if (!$wallet || $wallet->balance < $amount) {
                    throw new \Exception('Insufficient wallet balance.');
                }

                // 1. Deduct funds immediately
                $wallet->decrement('balance', $amount);

                try {
                    $payload = [
                        'request_id'        => $requestId,
                        'serviceID'         => $request->service_id,
                        'billersCode'       => $request->billersCode,
                        'subscription_type' => $request->subscription_type,
                        'amount'            => $amount,
                        'phone'             => $request->phone,
                    ];

                    if ($request->subscription_type === 'change') {
                        if (!$request->variation_code) {
                             throw new \Exception('Please select a plan for bouquet change.');
                        }
                        $payload['variation_code'] = $request->variation_code;
                    }

                    // 2. Call VTPass API
                    $response = Http::withHeaders([
                        'api-key'    => env('API_KEY'),
                        'secret-key' => env('SECRET_KEY'),
                    ])->post(env('MAKE_PAYMENT'), $payload);

                    if ($response->successful()) {
                        $result = $response->json();
                        
                        $successCodes = ['0', '00', '000', '200'];
                        $isSuccessful = (isset($result['code']) && in_array((string)$result['code'], $successCodes)) ||
                                        (isset($result['status']) && strtolower($result['status']) === 'success');

                        if ($isSuccessful) {
                            $serviceName = strtoupper($request->service_id);
                            $subType = ucfirst($request->subscription_type);
                            $description = "{$serviceName} Subscription ({$subType}) - IUC: {$request->billersCode}";

                            // Success: Create Transaction Record and commit
                            Transaction::create([
                                'referenceId'         => $requestId,
                                'user_id'             => $user->id,
                                'amount'              => $amount,
                                'service_type'        => 'cable',
                                'service_description' => $description,
                                'type'                => 'debit',
                                'status'              => 'Approved',
                                'gateway'             => 'vtpass',
                            ]);

                            // Report Record (Internal logging)
                            Report::create([
                                'user_id'      => $user->id,
                                'phone_number' => $request->billersCode,
                                'network'      => $request->service_id,
                                'ref'          => $requestId,
                                'amount'       => $amount,
                                'status'       => 'successful',
                                'type'         => 'cable',
                                'description'  => $description,
                                'old_balance'  => $wallet->balance + $amount,
                                'new_balance'  => $wallet->balance,
                            ]);

                            return redirect()->route('user.thankyou')->with([
                                'success' => 'Cable subscription successful!',
                                'ref'     => $requestId,
                                'mobile'  => $request->billersCode,
                                'amount'  => $amount,
                                'token'   => 'Subscription Active',
                                'network' => $serviceName
                            ]);
                        } else {
                            // API Failed: Rollback (via exception)
                            Log::error('Cable API Error', ['response' => $result]);
                            $errorMessage = $result['response_description'] ?? 'Subscription failed. Try again.';
                            throw new \Exception($errorMessage);
                        }
                    } else {
                        // HTTP Error: Rollback
                        Log::error('Cable HTTP Error', ['body' => $response->body()]);
                        throw new \Exception('Service unavailable.');
                    }

                } catch (\Exception $e) {
                    // API/Internal Exception: Rollback (is handled by the outer closure throwing)
                    Log::error('Cable refactor API error: ' . $e->getMessage());
                    throw $e;
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
