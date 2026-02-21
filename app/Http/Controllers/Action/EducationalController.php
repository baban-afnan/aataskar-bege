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


use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EducationalController extends Controller
{
    protected $loginUserId;

    public function __construct()
    {
        $this->loginUserId = Auth::id();
    }

    /**
     * Show Educational Pin Services & Price Lists
     */
    public function pin(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00, 'status' => 'active']
        );

        // Load pin variations
        $pins = DB::table('data_variations')->whereIn('service_id', ['waec', 'waec-registration'])->get();

        // Fetch purchase history
        $history = \App\Models\Report::where('user_id', $user->id)
            ->where('type', 'education')
            ->latest()
            ->paginate(10);

        return view('utilities.buy-educational-pin')->with(compact('pins', 'wallet', 'history'));
    }

    /**
     * Verify Transaction PIN
     */
    public function verifyPin(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['valid' => false, 'message' => 'Unauthorized']);
        }

        $isValid = Hash::check($request->pin, $user->pin);
        return response()->json(['valid' => $isValid]);
    }

    /**
     * Fetch variations dynamically from VTpass and store in DB
     */
    /**
     * Fetch variations dynamically from VTpass and store in DB
     */
    public function getVariation(Request $request)
    {
        try {
            // Determine serviceID based on type
            $type = $request->type;
            $url = env('VARIATION_URL') . $type;

            // Special handling for JAMB if needed, but usually VTPass uses 'jamb' as serviceID for variations too
            // If type is 'jamb', URL is .../service-variations?serviceID=jamb

            $response = Http::withHeaders([
                'api-key'    => env('API_KEY'),
                'secret-key' => env('SECRET_KEY'),
            ])->get($url);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['content']['variations'])) {
                    $serviceName = $data['content']['ServiceName'];
                    $serviceId = $data['content']['serviceID'];
                    $convenienceFee = $data['content']['convinience_fee'] ?? '0%';

                    foreach ($data['content']['variations'] as $variation) {
                        DB::table('data_variations')->updateOrInsert(
                            ['variation_code' => $variation['variation_code']],
                            [
                                'service_name'     => $serviceName,
                                'service_id'       => $serviceId,
                                'convenience_fee'  => $convenienceFee,
                                'name'             => $variation['name'],
                                'variation_amount' => $variation['variation_amount'],
                                'fixed_price'      => $variation['fixedPrice'],
                                'created_at'       => Carbon::now(),
                                'updated_at'       => Carbon::now(),
                            ]
                        );
                    }

                    return response()->json(['success' => true, 'message' => 'Variation list updated successfully.']);
                }
            }

            Log::error('VTpass Variation Fetch Failed', ['response' => $response->json()]);
            return response()->json(['success' => false, 'message' => 'Failed to fetch variations.']);
        } catch (\Exception $e) {
            Log::error('VTpass Variation Exception', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Something went wrong.']);
        }
    }

    /**
     * Buy Educational Pin (WAEC / WAEC Registration)
     */
    public function buypin(Request $request)
    {
        $request->validate([
            'service'  => ['required', 'string', 'in:waec-registration,waec'],
            'type'     => ['required', 'string'],
            'mobileno' => 'required|numeric|digits:11',
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        $requestId = RequestIdHelper::generateRequestId();

        // Get the selected variation details
        $variation = DB::table('data_variations')->where('variation_code', $request->type)->first();

        if (!$variation) {
            return back()->with('error', 'Invalid educational pin type selected.');
        }

        $fee = $variation->variation_amount;
        $description = $variation->name ?? 'Educational Pin';

        // Charge-first strategy with DB transaction
        try {
            return DB::transaction(function () use ($user, $fee, $requestId, $request, $description) {
                // Lock wallet for update to prevent race conditions
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                
                if (!$wallet || $wallet->balance < $fee) {
                    throw new \Exception('Insufficient wallet balance for this transaction.');
                }

                // 1. Deduct funds immediately
                $wallet->decrement('balance', $fee);

                try {
                    // 2. Call VTpass API
                    $response = Http::withHeaders([
                        'api-key'    => env('API_KEY'),
                        'secret-key' => env('SECRET_KEY'),
                    ])->post(env('MAKE_PAYMENT'), [
                        'request_id'     => $requestId,
                        'serviceID'      => $request->service,
                        'billersCode'    => '0123456789', // Dummy biller code for WAEC/Result Checker
                        'variation_code' => $request->type,
                        'phone'          => $request->mobileno,
                    ]);

                    if ($response->successful()) {
                        $result = $response->json();

                        // Check success codes
                        $successCodes = ['0', '00', '000', '200'];
                        $isSuccessful = (isset($result['code']) && in_array((string)$result['code'], $successCodes)) ||
                                        (isset($result['status']) && strtolower($result['status']) === 'success');

                        if ($isSuccessful) {
                            // Extract Purchased Code (PIN)
                            $purchasedCode = $result['purchased_code'] ?? null;
                            if (!$purchasedCode && isset($result['cards']) && is_array($result['cards']) && count($result['cards']) > 0) {
                                 $purchasedCode = $result['cards'][0]['Pin'] ?? null;
                            }
                            $finalToken = $purchasedCode ?? 'Check Transaction History';
                            $transDescription = "Educational pin purchase ({$description}) - PIN: {$finalToken}";

                            // Success: Create Transaction Record and commit
                            Transaction::create([
                                'referenceId'         => $requestId,
                                'user_id'             => $user->id,
                                'amount'              => $fee,
                                'service_type'        => 'education',
                                'service_description' => $transDescription,
                                'type'                => 'debit',
                                'status'              => 'Approved',
                                'gateway'             => 'vtpass',
                            ]);

                            // Create Report (Internal logging)
                            \App\Models\Report::create([
                                'user_id'      => $user->id,
                                'phone_number' => $request->mobileno,
                                'network'      => $request->service,
                                'ref'          => $requestId,
                                'amount'       => $fee,
                                'status'       => 'successful',
                                'type'         => 'education',
                                'description'  => $transDescription,
                                'old_balance'  => $wallet->balance + $fee,
                                'new_balance'  => $wallet->balance,
                            ]);

                            return redirect()->route('user.thankyou')->with([
                                'success' => 'Educational pin purchase successful!',
                                'ref'     => $requestId,
                                'mobile'  => $request->mobileno,
                                'amount'  => $fee,
                                'token'   => $finalToken,
                                'network' => strtoupper($request->service)
                            ]);
                        } else {
                            // API Failed: Rollback (via exception)
                            Log::error('VTpass Educational Pin API Error', ['response' => $result]);
                            $errorMessage = $result['response_description'] ?? 'Purchase failed. Please try again later.';
                            throw new \Exception($errorMessage);
                        }
                    } else {
                        // HTTP Error: Rollback
                        Log::error('VTpass Educational Pin HTTP Error', ['body' => $response->body()]);
                        throw new \Exception('Service temporarily unavailable. Try again later.');
                    }

                } catch (\Exception $e) {
                    // API/Internal Exception: Rollback
                    Log::error('Educational pin refactor API error: ' . $e->getMessage());
                    throw $e;
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show JAMB Purchase Page
     */
    public function jamb(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to access this page.');
        }

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00, 'status' => 'active']
        );

        // Fetch JAMB purchase history
        $history = \App\Models\Report::where('user_id', $user->id)
            ->where('type', 'jamb')
            ->latest()
            ->paginate(10);

        // Fetch JAMB variations
        $variations = DB::table('data_variations')->where('service_id', 'jamb')->get();

        return view('utilities.buy-jamb', compact('wallet', 'history', 'variations'));
    }

    /**
     * Verify JAMB Profile ID
     */
    public function verifyJamb(Request $request)
    {
        $request->validate([
            'service'    => 'required|string',
            'profile_id' => 'required|string',
        ]);

        try {
            // Map the selected service to the VTPass Service ID
            // Usually 'jamb' is the serviceID for both UTME and DE
            $vtpassServiceId = 'jamb'; 

            $response = Http::withHeaders([
                'api-key'    => env('API_KEY'),
                'secret-key' => env('SECRET_KEY'),
            ])->post(env('BASE_URL', 'https://sandbox.vtpass.com/api') . '/merchant-verify', [
                'serviceID'   => $vtpassServiceId,
                'billersCode' => $request->profile_id,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['code']) && $data['code'] == '000') {
                    $customerName = $data['content']['Customer_Name'] ?? 'Unknown';
                    
                    // Fetch price from DB
                    // The frontend sends 'jamb' or 'jamb-de' as the 'service' (which acts as variation code here)
                    // We look up the 'data_variations' table using this code.
                    // If not found, we might default to a known price or error.
                    
                    $variationCode = $request->service; // 'jamb' or 'jamb-de'
                    
                    // Try to find by variation_code directly
                    $variation = DB::table('data_variations')->where('variation_code', $variationCode)->first();
                    
                    // If not found, try to find by service_id 'jamb' and name like... (fallback)
                    if (!$variation) {
                         // Fallback: if user sent 'jamb', look for 'utme' maybe? 
                         // For now, let's assume the DB is seeded with 'jamb' and 'jamb-de' as variation_codes.
                         // If not, we return 0 and user can't buy.
                    }

                    $amount = $variation ? $variation->variation_amount : 0;

                    return response()->json([
                        'success' => true, 
                        'customer_name' => $customerName,
                        'amount' => $amount
                    ]);
                }
            }
            
            return response()->json(['success' => false, 'message' => 'Invalid Profile ID or Service unavailable.']);

        } catch (\Exception $e) {
            Log::error('JAMB Verification Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Verification failed.']);
        }
    }

    /**
     * Buy JAMB PIN
     */
    public function buyJamb(Request $request)
    {
        $request->validate([
            'service'    => 'required|string',
            'profile_id' => 'required|string',
            'mobileno'   => 'required|numeric|digits:11',
        ]);

        $user = Auth::user();
        $requestId = RequestIdHelper::generateRequestId();

        // Get Price
        $variation = DB::table('data_variations')->where('variation_code', $request->service)->first();
        if (!$variation) {
            return back()->with('error', 'Invalid JAMB service selected.');
        }

        $fee = $variation->variation_amount;
        $description = $variation->name ?? 'JAMB PIN';

        // Charge-first strategy with DB transaction
        try {
            return DB::transaction(function () use ($user, $fee, $requestId, $request, $description) {
                // Lock wallet for update to prevent race conditions
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                
                if (!$wallet || $wallet->balance < $fee) {
                    throw new \Exception('Insufficient wallet balance.');
                }

                // 1. Deduct funds immediately
                $wallet->decrement('balance', $fee);

                try {
                    // 2. Call API
                    $apiServiceId = 'jamb'; // Always 'jamb' for VTPass JAMB services

                    $response = Http::withHeaders([
                        'api-key'    => env('API_KEY'),
                        'secret-key' => env('SECRET_KEY'),
                    ])->post(env('MAKE_PAYMENT'), [
                        'request_id'     => $requestId,
                        'serviceID'      => $apiServiceId,
                        'billersCode'    => $request->profile_id,
                        'variation_code' => $request->service,
                        'phone'          => $request->mobileno,
                    ]);

                    if ($response->successful()) {
                        $result = $response->json();
                        
                        $successCodes = ['0', '00', '000', '200'];
                        $isSuccessful = (isset($result['code']) && in_array((string)$result['code'], $successCodes)) ||
                                        (isset($result['status']) && strtolower($result['status']) === 'success');

                        if ($isSuccessful) {
                            // Extract PIN
                            $purchasedCode = $result['purchased_code'] ?? null;
                            if (!$purchasedCode && isset($result['cards'][0]['Pin'])) {
                                $purchasedCode = $result['cards'][0]['Pin'];
                            }
                            $finalToken = $purchasedCode ?? 'Check History';
                            $transDescription = "{$description} Purchase - Profile: {$request->profile_id} - PIN: {$finalToken}";

                            // Success: Create Transaction Record and commit
                            Transaction::create([
                                'referenceId'         => $requestId,
                                'user_id'             => $user->id,
                                'amount'              => $fee,
                                'service_type'        => 'jamb',
                                'service_description' => $transDescription,
                                'type'                => 'debit',
                                'status'              => 'Approved',
                                'gateway'             => 'vtpass',
                            ]);

                            // Create Report (Internal logging)
                            \App\Models\Report::create([
                                'user_id'      => $user->id,
                                'phone_number' => $request->mobileno,
                                'network'      => $request->service,
                                'ref'          => $requestId,
                                'amount'       => $fee,
                                'status'       => 'successful',
                                'type'         => 'jamb',
                                'description'  => $transDescription,
                                'old_balance'  => $wallet->balance + $fee,
                                'new_balance'  => $wallet->balance,
                            ]);

                            return redirect()->route('user.thankyou')->with([
                                'success' => 'JAMB PIN purchase successful!',
                                'ref'     => $requestId,
                                'mobile'  => $request->mobileno,
                                'amount'  => $fee,
                                'token'   => $finalToken,
                                'network' => strtoupper($description)
                            ]);
                        } else {
                            // API Failed: Rollback (via exception)
                            Log::error('JAMB API Error', ['response' => $result]);
                            $errorMessage = $result['response_description'] ?? 'Purchase failed. Try again.';
                            throw new \Exception($errorMessage);
                        }
                    } else {
                        // HTTP Error: Rollback
                        Log::error('JAMB HTTP Error', ['body' => $response->body()]);
                        throw new \Exception('Service unavailable.');
                    }

                } catch (\Exception $e) {
                    // API/Internal Exception: Rollback
                    Log::error('JAMB refactor API error: ' . $e->getMessage());
                    throw $e;
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}


