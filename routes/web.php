<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\EnrollmentSyncController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Action\AirtimeController;
use App\Http\Controllers\Action\DataController;
use App\Http\Controllers\Action\EducationalController;
use App\Http\Controllers\Action\ElectricityController;
use App\Http\Controllers\Action\CableController;
use App\Http\Controllers\Verification\NINverificationController;
use App\Http\Controllers\Verification\NINDemoVerificationController;
use App\Http\Controllers\Verification\NINPhoneVerificationController;
use App\Http\Controllers\Agency\TinRegistrationController;
use App\Http\Controllers\Verification\BvnModificationController;
use App\Http\Controllers\Verification\BvnverificationController;
use App\Http\Controllers\Agency\NinValidationController;
use App\Http\Controllers\Agency\NinModificationController;
use App\Http\Controllers\Agency\IpeController;


Route::get('/', function () {
    return view('welcome');
});


Route::post('/palmpay/webhook', [PaymentWebhookController::class, 'handlePalmPay']);

Route::post('/update-bvn-enrollment-status', [EnrollmentSyncController::class, 'updateStatus']);

Route::group(['as' => 'auth.', 'prefix' => 'auth', 'middleware' => 'guest'], function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'showRegisterForm'])->name('register');

    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');

    Route::get('forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

    Route::get('reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// User Routes
Route::middleware(['auth', 'user.active'])->group(function () {
    // User dashboard
    Route::group(['as' => 'user.', 'prefix' => 'user'], function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/kyc-submit', [DashboardController::class, 'submitKyc'])->name('kyc.submit');
       

            //Wallet
            Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
            Route::get('claim-bonus/{id}', [WalletController::class, 'claimBonus'])->name('claim-bonus');

            //Transactions -----------------------------------------------------------------------------------------------------
            Route::get('/receipt/{referenceId}', [TransactionController::class, 'reciept'])->name('reciept');

          
            Route::get('/nin-services', [ServicesController::class, 'ninServices'])->name('nin.services');
            Route::post('/nin-services/request', [ServicesController::class, 'requestNinService'])->name('nin.services.request');

            Route::get('/nin-mod', [ServicesController::class, 'ninModification'])->name('nin.mod');
            Route::post('/nin-services/mod', [ServicesController::class, 'requestModification'])->name('nin.services.mod');


              // Utility Bill Payment Group
    Route::group([], function () {
        // Airtime
        Route::get('/airtime', [AirtimeController::class, 'airtime'])->name('airtime');
        Route::post('/buy-airtime', [AirtimeController::class, 'buyAirtime'])->name('buyairtime');

        // Data
        Route::get('/data', [DataController::class, 'data'])->name('buy-data');
        Route::post('/buy-data', [DataController::class, 'buydata'])->name('buydata');
        Route::get('/fetch-data-bundles', [DataController::class, 'fetchBundles'])->name('fetch.bundles');
        Route::get('/fetch-data-bundles-price', [DataController::class, 'fetchBundlePrice'])->name('fetch.bundle.price');
        Route::post('/verify-pin', [DataController::class, 'verifyPin'])->name('verify.pin');

        Route::get('/sme-data', [DataController::class, 'sme_data'])->name('sme-data');
        Route::get('/fetch-data-type', [DataController::class, 'fetchDataType']);
        Route::get('/fetch-data-plan', [DataController::class, 'fetchDataPlan']);
        Route::get('/fetch-sme-data-bundles-price', [DataController::class, 'fetchSmeBundlePrice']);
        Route::post('/buy-sme-data', [DataController::class, 'buySMEdata'])->name('buy-sme-data');

        // Education
        Route::get('/education', [EducationalController::class, 'pin'])->name("education");
        Route::post('/buy-pin', [EducationalController::class, 'buypin'])->name('buypin');
        Route::get('/education/receipt/{transaction}', [EducationalController::class, 'receipt'])->name('education.receipt');
        Route::get('/get-variation', [EducationalController::class, 'getVariation'])->name('get-variation');

        Route::get('/jamb', [EducationalController::class, 'jamb'])->name('jamb');
        Route::post('/verify-jamb', [EducationalController::class, 'verifyJamb'])->name('verify.jamb');
        Route::post('/buy-jamb', [EducationalController::class, 'buyJamb'])->name('buyjamb');

        // Electricity
        Route::get('/electricity', [ElectricityController::class, 'index'])->name('electricity');
        Route::post('/verify-electricity', [ElectricityController::class, 'verifyMeter'])->name('verify.electricity');
        Route::post('/buy-electricity', [ElectricityController::class, 'purchase'])->name('buy.electricity');

        // Cable
        Route::get('/cable', [CableController::class, 'index'])->name('cable');
        Route::get('/cable/variations', [CableController::class, 'getVariations'])->name('cable.variations');
        Route::post('/cable/verify', [CableController::class, 'verifyIuc'])->name('verify.cable');
        Route::post('/cable/buy', [CableController::class, 'purchase'])->name('buy.cable');
        Route::get('/thankyou', function () {
            return view('user.thankyou');
        })->name('thankyou');
    });

      // Verification Services Group
    Route::group([], function () {
        // NIN Verification
        Route::prefix('nin-verification')->as('nin.verification.')->group(function () {
            Route::get('/', [NINverificationController::class, 'index'])->name('index');
            Route::post('/', [NINverificationController::class, 'store'])->name('store');
            Route::post('/{id}/status', [NINverificationController::class, 'updateStatus'])->name('status');
            Route::get('/standardSlip/{id}', [NINverificationController::class, 'standardSlip'])->name('standard');
            Route::get('/premiumSlip/{id}', [NINverificationController::class, 'premiumSlip'])->name('premium');
            Route::get('/vninSlip/{id}', [NINverificationController::class, 'vninSlip'])->name('vnin');
        });


        /*
        |--------------------------------------------------------------------------
        | NIN Demographic Verification
        |--------------------------------------------------------------------------
        */
        Route::prefix('nin-demo-verification')->as('nin.demo.')->group(function () {
            Route::get('/', [NINDemoVerificationController::class, 'index'])->name('index');
            Route::post('/', [NINDemoVerificationController::class, 'store'])->name('store');
            Route::get('/freeSlip/{id}', [NINDemoVerificationController::class, 'freeSlip'])->name('free');
            Route::get('/regularSlip/{id}', [NINDemoVerificationController::class, 'regularSlip'])->name('regular');
            Route::get('/standardSlip/{id}', [NINDemoVerificationController::class, 'standardSlip'])->name('standard');
            Route::get('/premiumSlip/{id}', [NINDemoVerificationController::class, 'premiumSlip'])->name('premium');
        });

         // NIN Modification
        Route::prefix('nin-modification')->as('nin.modification.')->group(function () {
            Route::get('/', [NinModificationController::class, 'index'])->name('index');
            Route::post('/', [NinModificationController::class, 'store'])->name('store');
            Route::get('/check/{id}', [NinModificationController::class, 'checkStatus'])->name('check');
        });

        // NIN Validation Routes
        Route::prefix('nin-validation')->as('nin.validation.')->group(function () {
            Route::get('/', [NinValidationController::class, 'index'])->name('index');
            Route::post('/', [NinValidationController::class, 'store'])->name('store');
            Route::get('/check/{id}', [NinValidationController::class, 'checkStatus'])->name('check');
        });

      // IPE Routes
Route::prefix('ipe')->as('ipe.')->group(function () {
    Route::get('/', [IpeController::class, 'index'])->name('index');
    Route::post('/', [IpeController::class, 'store'])->name('store');
    Route::get('/check/{id}', [IpeController::class, 'checkStatus'])->name('check');
    Route::get('/{id}/details', [IpeController::class, 'details'])->name('details');
    Route::post('/batch-check', [IpeController::class, 'batchCheck'])->name('batch-check');
});

        /*
        |--------------------------------------------------------------------------
        | NIN Phone Verification
        |--------------------------------------------------------------------------
        */
        Route::prefix('nin-phone-verification')->as('nin.phone.')->group(function () {
            Route::get('/', [NINPhoneVerificationController::class, 'index'])->name('index');
            Route::post('/', [NINPhoneVerificationController::class, 'store'])->name('store');
            Route::get('/regularSlip/{id}', [NINPhoneVerificationController::class, 'regularSlip'])->name('regular');
            Route::get('/standardSlip/{id}', [NINPhoneVerificationController::class, 'standardSlip'])->name('standard');
            Route::get('/premiumSlip/{id}', [NINPhoneVerificationController::class, 'premiumSlip'])->name('premium');
        });

        // BVN Verification
        Route::prefix('bvn-verification')->group(function () {
            Route::get('/', [BvnverificationController::class, 'index'])->name('bvn-verification');
            Route::post('/', [BvnverificationController::class, 'store'])->name('bvn.verification.store');
            Route::get('/standardBVN/{id}', [BvnverificationController::class, 'standardBVN'])->name("standardBVN");
            Route::get('/premiumBVN/{id}', [BvnverificationController::class, 'premiumBVN'])->name("premiumBVN");
            Route::get('/plasticBVN/{id}', [BvnverificationController::class, 'plasticBVN'])->name("plasticBVN");
            Route::get('/vninSlip/{id}', [NINverificationController::class, 'vninSlip'])->name('vninSlip');
        });

       // TIN Registration
        Route::prefix('tin-reg')->group(function () {
            Route::get('/', [TinRegistrationController::class, 'index'])->name('tin.index');
            Route::post('/validate', [TinRegistrationController::class, 'validateTin'])->name('tin.validate');
            Route::post('/download', [TinRegistrationController::class, 'downloadSlip'])->name('tin.download');
        });

        
        Route::get('/modification-fields/{serviceId}', [BvnModificationController::class, 'getServiceFields'])->name('modification.fields');
        Route::get('/modification', [BvnModificationController::class, 'index'])->name('modification');
        Route::post('/modification', [BvnModificationController::class, 'store'])->name('modification.store');
        Route::get('/modification/check/{id}', [BvnModificationController::class, 'checkStatus'])->name('modification.check');

            //Whatsapp API Support--------------------------------------------------------------------------
            Route::get('/support', function () {
                $phoneNumber = env('phoneNumber');
                $message = urlencode(env('message'));
                $url = env('API_URL') . "{$phoneNumber}&text={$message}";
                return redirect($url);
            })->name('support');
        });

        Route::get('/profile', function () {
            return view('user.profile');
        })->name('profile');

        Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    });
    // Logout Route
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Admin Routes
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth', 'user.active', 'user.admin']], function () {


    Route::get('/receipt/{referenceId}', [TransactionController::class, 'recieptAdmin'])->name('reciept');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('user.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('user.update');
    Route::patch('/users/{user}/activate', [UserController::class, 'activate'])->name('user.activate');

    Route::get('/transactions', [TransactionController::class, 'transactions'])->name('transactions');


    Route::get('/bvn-services', [ServicesController::class, 'bvnServicesList'])->name('bvn.services.list');
    Route::post('/requests/{id}/{type}/update-bvn-status', [ServicesController::class, 'updateBvnRequestStatus'])->name('bvn-update-request-status');
    Route::get('/view-bvn-request/{id}/{type}/edit', [ServicesController::class, 'showBvnRequests'])->name('bvn-view-request');


    Route::get('/mod-services', [ServicesController::class, 'modServicesList'])->name('mod.services.list');
    Route::post('/requests/{id}/{type}/update-mod-status', [ServicesController::class, 'updateModRequestStatus'])->name('mod-update-request-status');
    Route::get('/view-mod-request/{id}/{type}/edit', [ServicesController::class, 'showModRequests'])->name('mod-view-request');

    // Services
    Route::get('/services', [ServicesController::class, 'index'])->name('services.index');
    Route::get('/services/edit/{id}', [ServicesController::class, 'edit'])->name('services.edit');
    Route::put('/services/update/{id}', [ServicesController::class, 'update'])->name('services.update');

    //NIN Services
    Route::get('/nin-services', [ServicesController::class, 'ninServicesList'])->name('nin.services.list');
    Route::post('/requests/{id}/{type}/update-status', [ServicesController::class, 'updateRequestStatus'])->name('update-request-status');
    Route::get('/view-request/{id}/{type}/edit', [ServicesController::class, 'showRequests'])->name('view-request');
});
});
