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
use Illuminate\Support\Facades\DB;

class AirtimeController extends Controller
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
     * Show Airtime purchase form
     */
    public function airtime()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('auth.login')->with('error', 'Please log in to access this page.');
        }

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0.00, 'status' => 'active']
        );

        return view('utilities.airtime', [
            'user'   => $user,
            'wallet' => $wallet,
        ]);
    }

    /**
     * Handle Airtime Purchase
     */
    /**
     * Handle Airtime Purchase
     */
    public function buyAirtime(Request $request)
    {
        $request->validate([
            'network'   => ['required', 'string', 'in:mtn,airtel,glo,etisalat'],
            'mobileno'  => 'required|numeric|digits:11',
            'amount'    => 'required|numeric|min:50|max:10000',
        ]);

        $user   = Auth::user();
        $networkKey = strtolower($request->network); // mtn, airtel, etc.
        $mobile  = $request->mobileno;
        $amount  = $request->amount;
        $requestId = RequestIdHelper::generateRequestId();

        // Map network names to Arewa Smart API codes
        $networkCodes = [
            'airtel' => '100',
            'mtn'    => '101',
            'glo'    => '102',
            'etisalat' => '103', // 9mobile
        ];
        $networkCode = $networkCodes[$networkKey];

        // 1. Find the Airtime Service
        $service = Services1::where('name', 'Airtime')->first();
        if (!$service) {
             $service = Services1::firstOrCreate(['name' => 'Airtime'], ['status' => 'active']);
        }

        // 2. Find the specific Network Field (e.g., MTN)
        $serviceField = \App\Models\ServiceField::where('service_id', $service->id)
            ->where(function($q) use ($networkKey) {
                $q->where('field_name', 'LIKE', "%{$networkKey}%")
                  ->orWhere('field_code', 'LIKE', "%{$networkKey}%");
            })->first();

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
        $payableAmount = $amount - $discountAmount;

        // Charge-first strategy with DB transaction
        try {
            return DB::transaction(function () use ($user, $payableAmount, $mobile, $amount, $networkCode, $networkKey, $requestId, $discountAmount) {
                // Lock wallet for update to prevent race conditions
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                
                if (!$wallet || $wallet->balance < $payableAmount) {
                    throw new \Exception('Insufficient wallet balance! You need ₦' . number_format($payableAmount, 2));
                }

                // 1. Deduct funds immediately
                $wallet->decrement('balance', $payableAmount);

                try {
                    // 2. Call Arewa Smart Airtime API
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->getApiToken(),
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                    ])->post($this->getApiBaseUrl() . '/airtime/purchase', [
                        'network'    => $networkCode,
                        'mobileno'   => $mobile,
                        'amount'     => $amount,
                        'request_id' => $requestId,
                    ]);

                    $data = $response->json();
                    Log::info('Arewa Smart API Response', ['response' => $data]);

                    if ($response->successful() && isset($data['status']) && $data['status'] === 'success') {
                        // Success: Create Transaction Record and commit
                        $apiData = $data['data'] ?? [];
                        $transactionRef = $apiData['transaction_ref'] ?? $requestId;
                        $commissionEarned = $apiData['commission_earned'] ?? 0;

                        Transaction::create([
                            'referenceId'         => $transactionRef,
                            'user_id'             => $user->id,
                            'amount'              => $payableAmount,
                            'service_type'        => 'airtime',
                            'service_description' => "Airtime purchase of ₦{$amount} for {$mobile} ({$networkKey})",
                            'type'                => 'debit',
                            'status'              => 'Approved',
                            'gateway'             => 'arewa_smart',
                        ]);

                        return redirect()->route('user.thankyou')->with([
                            'success'           => 'Airtime purchase successful!',
                            'transaction_ref'   => $transactionRef,
                            'request_id'        => $requestId,
                            'mobile'            => $mobile,
                            'network'           => ucfirst($networkKey),
                            'amount'            => $amount,
                            'paid'              => $payableAmount,
                            'commission_earned' => $commissionEarned,
                            'type'              => 'airtime'
                        ]);
                    } else {
                        // API Failed: Rollback (via exception)
                        $errorMessage = $data['message'] ?? 'Airtime purchase failed. Please try again.';
                        throw new \Exception($errorMessage);
                    }

                } catch (\Exception $e) {
                    // API Exception: Rollback (is handled by the outer closure throwing)
                    Log::error('Arewa Smart Airtime API error: ' . $e->getMessage());
                    throw $e;
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
