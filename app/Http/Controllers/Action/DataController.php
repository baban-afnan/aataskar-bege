<?php

namespace App\Http\Controllers\Action;

use App\Helpers\RequestIdHelper;
use App\Http\Controllers\Controller;
use App\Models\Services1;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
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
     * Show Data Services & Price Lists
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('auth.login')->with('error', 'Please log in to access this page.');
        }

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00, 'status' => 'active']
        );

        try {
            // Fetch services that end with 'data' or are relevant data services
            $data_variation = DB::table('data_variations')
                ->select(['service_id', 'service_name'])
                ->where('status', 'enabled')
                ->where(function ($query) {
                    $query->where('service_id', 'LIKE', '%data')
                        ->orWhere('service_id', 'smile-direct')
                        ->orWhere('service_id', 'spectranet');
                })
                ->distinct()
                ->limit(6)
                ->get();

            // Fetch Price Lists (Paginated)
            $priceList1 = DB::table('data_variations')->where('service_id', 'mtn-data')->paginate(10, ['*'], 'mtn_page');
            $priceList2 = DB::table('data_variations')->where('service_id', 'airtel-data')->paginate(10, ['*'], 'airtel_page');
            $priceList3 = DB::table('data_variations')->where('service_id', 'glo-data')->paginate(10, ['*'], 'glo_page');
            $priceList4 = DB::table('data_variations')->where('service_id', 'etisalat-data')->paginate(10, ['*'], '9mobile_page');
            $priceList5 = DB::table('data_variations')->where('service_id', 'smile-direct')->paginate(10, ['*'], 'smile_page');
            $priceList6 = DB::table('data_variations')->where('service_id', 'spectranet')->paginate(10, ['*'], 'spectranet_page');

            return view('utilities.buy-data', compact('user', 'wallet', 'data_variation', 'priceList1', 'priceList2', 'priceList3', 'priceList4', 'priceList5', 'priceList6'));
        } catch (\Exception $e) {
            Log::error('Data page error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load data services.');
        }
    }

    /**
     * Verify transaction PIN
     */
    public function verifyPin(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['valid' => false, 'message' => 'Unauthorized']);
        }

        // Direct comparison since PIN is stored as plain text (5 digits)
        $isValid = ($request->pin === $user->pin);
        return response()->json(['valid' => $isValid]);
    }

    /**
     * Sync Variations from API
     */
    public function getVariation(Request $request)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getApiToken(),
                'Accept' => 'application/json',
            ])->get($this->getApiBaseUrl() . '/data/variations');

            if ($response->successful()) {
                $data = $response->json();

                // Parse the new API response format
                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $variation) {
                        // Extract service info from variation_code (e.g., "mtn-10mb-100")
                        $variationCode = $variation['variation_code'] ?? '';
                        $parts = explode('-', $variationCode);
                        $network = $parts[0] ?? 'unknown';

                        DB::table('data_variations')->updateOrInsert(
                            ['variation_code' => $variationCode],
                            [
                                'service_name'    => ucfirst($network) . ' Data',
                                'service_id'      => $network . '-data',
                                'convinience_fee' => 0,
                                'name'            => $variation['name'] ?? $variationCode,
                                'variation_amount' => $variation['price'] ?? 0,
                                'fixedPrice'      => 'Yes',
                                'status'          => 'enabled',
                                'created_at'      => Carbon::now(),
                                'updated_at'      => Carbon::now()
                            ]
                        );
                    }

                    return response()->json(['success' => true, 'message' => 'Variations synced successfully']);
                }
            }

            return response()->json(['success' => false, 'message' => 'Failed to fetch variations'], 400);
        } catch (\Exception $e) {
            Log::error('Get variation error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error syncing variations'], 500);
        }
    }

    /**
     * Buy Data Bundle
     */
    public function buydata(Request $request)
    {
        $request->validate([
            'network'  => 'required|string',
            'mobileno' => 'required|numeric|digits:11',
            'bundle'   => 'required|string'
        ]);

        $user = Auth::user();
        $networkKey = $request->network; // e.g., mtn-data
        $mobile = $request->mobileno;
        $requestId = RequestIdHelper::generateRequestId();

        // Fetch Bundle Details from database
        $variation = DB::table('data_variations')->where('variation_code', $request->bundle)->first();
        if (!$variation) {
            return back()->with('error', 'Invalid data bundle selected.');
        }

        $amount = $variation->variation_amount; // Face value / API price
        $description = $variation->name ?? 'Data Bundle';

        // 1. Find the Data Service
        $service = Services1::where('name', 'Data')->first();
        if (!$service) {
            $service = Services1::firstOrCreate(['name' => 'Data'], ['status' => 'active']);
        }

        // Calculate Payable Amount (Discount logic simplified for now as per instructions)
        // You can re-enable discount logic here if needed, similar to AirtimeController adjustments if any
        $payableAmount = $amount; 

        // Charge-first strategy with DB transaction
        try {
            return DB::transaction(function () use ($user, $payableAmount, $networkKey, $mobile, $requestId, $variation, $amount, $description, $request) {
                // Lock wallet for update to prevent race conditions
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                
                if (!$wallet || $wallet->balance < $payableAmount) {
                    throw new \Exception('Insufficient wallet balance! You need ₦' . number_format($payableAmount, 2));
                }

                // 1. Deduct funds immediately
                $wallet->decrement('balance', $payableAmount);

                try {
                    // 2. Call Arewa Smart Data API
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->getApiToken(),
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                    ])->post($this->getApiBaseUrl() . '/data/purchase', [
                        'network'    => $networkKey,
                        'mobileno'   => $mobile,
                        'bundle'     => $request->bundle,
                        'request_id' => $requestId,
                    ]);

                    $data = $response->json();
                    Log::info('Arewa Smart Data API Response', ['response' => $data]);

                    if ($response->successful() && isset($data['status']) && $data['status'] === 'success') {
                        // Success: Create Transaction Record and commit
                        $apiData = $data['data'] ?? [];
                        $transactionRef = $apiData['transaction_ref'] ?? $requestId;

                        Transaction::create([
                            'referenceId'         => $transactionRef,
                            'user_id'             => $user->id,
                            'amount'              => $payableAmount,
                            'service_type'        => 'data',
                            'service_description' => "Data purchase of {$description} for {$mobile}",
                            'type'                => 'debit',
                            'status'              => 'Approved',
                            'gateway'             => 'arewa_smart',
                        ]);

                        return redirect()->route('user.thankyou')->with([
                            'success'         => 'Data purchase successful!',
                            'transaction_ref' => $transactionRef,
                            'request_id'      => $requestId,
                            'mobile'          => $mobile,
                            'network'         => ucfirst(str_replace('-data', '', $networkKey)),
                            'amount'          => $amount,
                            'paid'            => $payableAmount,
                            'type'            => 'data'
                        ]);
                    } else {
                        // API Failed: Rollback (via exception)
                        $errorMessage = $data['message'] ?? 'Data purchase failed. Please try again.';
                        throw new \Exception($errorMessage);
                    }

                } catch (\Exception $e) {
                    // API Exception: Rollback (is handled by the outer closure throwing)
                    Log::error('Arewa Smart Data API error: ' . $e->getMessage());
                    throw $e;
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Fetch Bundles by Service ID
     */
    public function fetchBundles(Request $request)
    {
        try {
            $bundles = DB::table('data_variations')
                ->select(['name', 'variation_code'])
                ->where('service_id', $request->id)
                ->where('status', 'enabled')
                ->get();

            return response()->json($bundles);
        } catch (\Exception $e) {
            Log::error('Fetch bundles error: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Fetch Bundle Price
     */
    public function fetchBundlePrice(Request $request)
    {
        try {
            $price = DB::table('data_variations')
                ->where('variation_code', $request->id)
                ->value('variation_amount');

            return response()->json(number_format((float)$price, 2));
        } catch (\Exception $e) {
            Log::error('Fetch bundle price error: ' . $e->getMessage());
            return response()->json("0.00", 500);
        }
    }

    /*
     * Fetch Data Type
     */
    public function fetchDataType(Request $request)
    {
        // Implement logic if needed, or remove if unused relative to previous code. 
        // For now adhering to core data purchase logic.
        return response()->json([]);
    }
}
