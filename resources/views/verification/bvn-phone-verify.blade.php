@extends('layouts.dashboard')

@section('title', 'Instant BVN Verification')
@push('styles')
    <style>
        #overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 18px;
            z-index: 9999;
        }

        #overlay button {
            margin-top: 20px;
            padding: 10px 20px;
            background: #ff5252;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }
    </style>
@endpush
@section('content')
    <div class="row">
        <div class="mb-3 mt-1">
            <h4 class="mb-1">Welcome back, {{ auth()->user()->name ?? 'User' }} 👋</h4>
        </div>
        <div class="col-lg-12 grid-margin d-flex flex-column">
            <div class="row">

                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title m-0">
                                Instant BVN Verify
                            </div>
                        </div>
                        <div class="card-body ">

                            <div class="alert alert-danger shadow-sm">
                                <center><svg class="d-block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                        width="36" height="36" fill="currentColor">
                                        <path
                                            d="M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM11 11V17H13V11H11ZM11 7V9H13V7H11Z">
                                        </path>
                                    </svg>
                                    <p> Note that N{{ $BVNFee->amount }} fee will be deducted from your wallet balance for
                                        each
                                        successful verification. There will be no charges on instances where the BVN is not
                                        successfully retrieved or if the data is not found.</p>

                                    <p>Please confirm you have sufficient funds in your wallet before proceeding with
                                        the verification.</p>
                                </center>

                            </div>

                            <div class="alert alert-danger alert-dismissible text-center" id="errorMsg"
                                style="display:none;" role="alert">
                                <small id="message">Processing your request.</small>
                            </div>
                            <div class="row text-center">
                                <div class="col-md-12">
                                    <form id="verifyForm" name="verifyForm" method="POST">
                                        @csrf
                                        <div class="row mb-3">
                                            <div class="col-md-12 mx-auto">
                                            </div>
                                            <div class="col-md-12">
                                                <p class="text-muted mb-2">Enter Phone Number</p>
                                                <input type="text" id="bvn" name="phoneNumber" value=""
                                                    class="form-control text-center" maxlength="11" required />
                                            </div>
                                            <div class="col-md-12 mx-auto">
                                            </div>
                                        </div>
                                        <button type="submit" id="verifyBVN" class="btn btn-primary"><i
                                                class="lar la-check-circle"></i>
                                            Check BVN
                                            Details</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-12 mt-2">
                    <div class="card custom-card">
                        <div class="card-header justify-content-between">
                            <div class="card-title m-0">
                                <i class="ri-user-search-line fw-bold"></i> Verified Information
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="col-md-12 row">
                                <div class="alert alert-danger alert-dismissible text-center" id="errorMsg2"
                                    style="display:none;" role="alert">
                                    <small id="message2">Processing your request.</small>
                                </div>

                                <div class="validation-info col-md-12 mb-2 hidden" id="validation-info">
                                    <center>
                                        <img src="{{ asset('assets/images/search.png') }}" width="20%" alt="Search Icon">
                                        <p class="mt-5">This section will display search results </p>
                                    </center>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="overlay">
        <div class="text-center">
            <p>To use this page, pop-ups must be enabled. Please allow pop-ups for this site.</p>
            <button onclick="enablePopups()">Allow Pop-ups</button>
        </div>
    </div>
    <div id="responsive-overlay"></div>
@endsection
@push('scripts')
    <script src="{{ asset('assets/js/bvnPhone.js') }}"></script>
    <script>
        function enablePopups() {
            const testPopup = window.open('', '_blank', 'width=1,height=1');
            if (testPopup === null || typeof testPopup === 'undefined') {
                alert("Pop-ups are still blocked. Please allow pop-ups in your browser settings.");
            } else {

                testPopup.close();
                localStorage.setItem('popupsAllowed', 'true');
                document.getElementById('overlay').style.display = 'none';
                window.location.reload();
            }
        }
        window.onload = function() {
            if (localStorage.getItem('popupsAllowed') === 'true') {
                document.getElementById('overlay').style.display = 'none';
                return;
            }
            const testPopup = window.open('', '_blank', 'width=1,height=1');
            if (testPopup === null || typeof testPopup === 'undefined') {
                document.getElementById('overlay').style.display = 'flex';
            } else {
                testPopup.close();
                localStorage.setItem('popupsAllowed', 'true');
                document.getElementById('overlay').style.display = 'none';
            }
        };
    </script>
@endpush
