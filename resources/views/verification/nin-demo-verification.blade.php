@extends('layouts.dashboard')

@section('title', 'NIN Demographic Verification')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-info bg-gradient border-0 shadow-sm rounded-3">
                <div class="card-body p-4 text-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="fw-bold mb-1">NIN Demographic Verification</h3>
                            <p class="text-white text-opacity-75 mb-0">Verify identity details using demographic information.</p>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded-circle">
                            <i class="mdi mdi-account-details text-white fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Demographic Verification Form -->
        <div class="col-xl-5 mb-4">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="mdi mdi-badge-account me-2 text-info"></i>Verify Identity</h5>
                </div>

                <div class="card-body p-4">
                    {{-- Alerts --}}
                    @if (session('status') && session('message'))
                        <div class="alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }} alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <i class="mdi mdi-{{ session('status') === 'success' ? 'check-circle' : 'alert-circle' }} me-2"></i>
                            {{ session('message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                            <ul class="mb-0 small ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('user.nin.demo.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small text-uppercase">First Name</label>
                                <input class="form-control bg-light border-0" name="firstName" type="text"
                                    placeholder="First Name" required value="{{ old('firstName') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small text-uppercase">Last Name</label>
                                <input class="form-control bg-light border-0" name="lastName" type="text"
                                    placeholder="Last Name" required value="{{ old('lastName') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small text-uppercase">Gender</label>
                                <select class="form-select bg-light border-0" name="gender" required>
                                    <option value="" disabled selected>Select</option>
                                    <option value="M" {{ old('gender') == 'M' ? 'selected' : '' }}>Male</option>
                                    <option value="F" {{ old('gender') == 'F' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-muted small text-uppercase">Date of Birth</label>
                                <input class="form-control bg-light border-0" name="dateOfBirth" type="text"
                                    placeholder="DD-MM-YYYY" required value="{{ old('dateOfBirth') }}">
                                <small class="text-muted italic small">Format: 20-02-1966</small>
                            </div>

                            <div class="col-12 mt-4">
                                <div class="card bg-light border-0 rounded-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small">Service Charge:</span>
                                            <span class="fw-bold text-dark">₦{{ number_format($demoPrice ?? 0, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small">Wallet Balance:</span>
                                            <span class="fw-bold text-info">₦{{ number_format($wallet->balance ?? 0, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 d-grid mt-3">
                                <button class="btn btn-info w-100 btn-lg fw-bold py-3 rounded-3 shadow-sm hover-up text-white" type="submit">
                                    <i class="mdi mdi-magnify me-2"></i> VERIFY DEMOGRAPHIC
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Verification Result -->
        <div class="col-xl-7 mb-4">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark"><i class="mdi mdi-account-card-details me-2 text-info"></i>Verification Result</h5>
                </div>

                <div class="card-body p-4">
                    @if (session('verification'))
                        <div class="alert alert-soft-info border-0 rounded-3 mb-4 d-flex align-items-center" style="background-color: #e3f2fd; color: #0d47a1;">
                            <i class="mdi mdi-check-decagram fs-4 me-2"></i>
                            <strong>Verification Successful!</strong>
                        </div>

                        @php
                            $verificationData = session('verification')['data'] ?? [];
                        @endphp

                        <div class="row align-items-center">
                            <div class="col-md-4 text-center mb-4 mb-md-0">
                                <div class="d-inline-block p-2 border border-2 border-info rounded-4 bg-white shadow-sm overflow-hidden" style="width: 160px; height: 180px;">
                                    @if (!empty($verificationData['photo']))
                                        <img src="data:image/jpeg;base64,{{ $verificationData['photo'] }}"
                                            alt="ID Photo" class="w-100 h-100 rounded-3"
                                            style="object-fit: cover;">
                                    @else
                                        <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center">
                                            <i class="mdi mdi-account-outline fs-1 text-muted"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-2 fw-bold text-uppercase small text-muted">Passport</div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="table-responsive rounded-3 overflow-hidden border">
                                    <table class="table table-hover mb-0">
                                        <tbody class="small">
                                            <tr>
                                                <th class="bg-light w-40 text-muted py-2 ps-3">NIN Number</th>
                                                <td class="fw-bold text-info py-2">{{ $verificationData['nin'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-muted py-2 ps-3">Surname</th>
                                                <td class="fw-semibold py-2 text-uppercase">{{ $verificationData['surname'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-muted py-2 ps-3">First Name</th>
                                                <td class="fw-semibold py-2">{{ $verificationData['firstName'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-muted py-2 ps-3">Middle Name</th>
                                                <td class="fw-semibold py-2">{{ $verificationData['middleName'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-muted py-2 ps-3">DOB</th>
                                                <td class="fw-semibold py-2">
                                                    {{ !empty($verificationData['birthDate'])
                                                        ? \Carbon\Carbon::parse($verificationData['birthDate'])->format('d M, Y')
                                                        : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-muted py-2 ps-3">Gender</th>
                                                <td class="fw-semibold py-2">{{ strtoupper($verificationData['gender'] ?? 'N/A') }}</td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light text-muted py-2 ps-3">Phone</th>
                                                <td class="fw-semibold py-2">{{ $verificationData['telephoneNo'] ?? 'N/A' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-top">
                            <h6 class="fw-bold mb-3 text-center text-muted small text-uppercase"><i class="mdi mdi-download me-2"></i>Download Slips</h6>
                            <div class="row g-2 text-center justify-content-center">
                                <div class="col-4">
                                    <button onclick="confirmDownload('{{ route('user.nin.demo.regular', $verificationData['nin']) }}', 'Regular Slip', {{ $regularSlipPrice ?? 0 }})" 
                                        class="btn btn-outline-info w-100 py-2 rounded-3 border-2">
                                        <i class="mdi mdi-file-document-outline me-1"></i> Regular <br>
                                        <small class="fw-bold">₦{{ number_format($regularSlipPrice ?? 0, 2) }}</small>
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button onclick="confirmDownload('{{ route('user.nin.demo.standard', $verificationData['nin']) }}', 'Standard Slip', {{ $standardSlipPrice ?? 0 }})" 
                                        class="btn btn-outline-warning w-100 py-2 rounded-3 border-2">
                                        <i class="mdi mdi-file-document-outline me-1"></i> Standard <br>
                                        <small class="fw-bold">₦{{ number_format($standardSlipPrice ?? 0, 2) }}</small>
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button onclick="confirmDownload('{{ route('user.nin.demo.premium', $verificationData['nin']) }}', 'Premium Slip', {{ $premiumSlipPrice ?? 0 }})" 
                                        class="btn btn-primary bg-gradient w-100 py-2 rounded-3 border-0">
                                        <i class="mdi mdi-file-star-outline me-1 text-white"></i> Premium <br>
                                        <small class="fw-bold text-white text-opacity-75">₦{{ number_format($premiumSlipPrice ?? 0, 2) }}</small>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                                <i class="mdi mdi-account-search fs-1 text-muted"></i>
                            </div>
                            <h6 class="text-muted fw-bold">No results to display</h6>
                            <p class="small text-muted mb-0">Enter the identity details and click verify.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .hover-up { transition: transform 0.2s ease; }
    .hover-up:hover { transform: translateY(-3px); }
    .bg-gradient { background: linear-gradient(45deg, #0dcaf0 0%, #0aa2c0 100%) !important; }
    .alert-soft-info { border-left: 4px solid #0d47a1 !important; }
    .table-hover tbody tr:hover { background-color: rgba(13, 202, 240, 0.05); }
    .italic { font-style: italic; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    @if (session('status') === 'success')
        window.addEventListener('load', () => {
            const speak = () => {
                const message = "Demographic verification is successful. Personal records have been retrieved correctly.";
                const utterance = new SpeechSynthesisUtterance(message);
                const voices = window.speechSynthesis.getVoices();
                const femaleVoice = voices.find(voice => 
                    ['female', 'samantha', 'victoria', 'google uk english female'].some(v => voice.name.toLowerCase().includes(v))
                );
                if (femaleVoice) utterance.voice = femaleVoice;
                utterance.rate = 1.0;
                utterance.pitch = 1.1;
                window.speechSynthesis.speak(utterance);
                return true;
            };
            if (!speak()) window.speechSynthesis.onvoiceschanged = speak;
        });
    @endif

    function confirmDownload(url, type, price) {
        Swal.fire({
            title: 'Download Confirmation',
            text: `You will be charged ₦${price.toLocaleString()} for the ${type}.`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#0dcaf0',
            cancelButtonColor: '#ff4d6d',
            confirmButtonText: '<i class="mdi mdi-download me-1"></i> Yes, Download',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-info text-white px-4 py-2 rounded-3',
                cancelButton: 'btn btn-danger px-4 py-2 rounded-3 ms-2'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) window.location.href = url;
        });
    }
</script>
@endpush
