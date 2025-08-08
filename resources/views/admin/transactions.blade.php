@extends('layouts.dashboard')

@section('title', 'All Transactions')
@push('styles')
    <style>
        .pagination .page-link {
            min-width: 36px;
            text-align: center;
        }

        @media (max-width: 576px) {
            .pagination {
                font-size: 0.75rem;
            }
        }
    </style>
@endpush
@section('content')
    <div class="row">
        <div class="mb-3 mt-1">
            <h4 class="mb-1">Welcome back, {{ auth()->user()->name ?? 'User' }} 👋</h4>
            <p class="mb-0">Here’s a quick look at your dashboard.</p>
        </div>

        @include('common.message')

        <div class="col-lg-12 grid-margin">
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="row">

                                <div class="mb-3">
                                    <form method="GET" action="{{ url()->current() }}">
                                        <div class="input-group w-50">
                                            <input type="text" name="search" value="{{ request('search') }}"
                                                class="form-control" placeholder="Search transactions...">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <div class="table-responsive">
                                    @if ($transactions->count())
                                        <p>Showing {{ $transactions->total() }}
                                            {{ Str::plural('record', $transactions->total()) }}</p>
                                        <small class="text-danger">Click on the reference number to generate a transaction
                                            receipt or use the download button</small>

                                        <table class="table text-nowrap" style="background:#fafafc !important">
                                            <thead>
                                                <tr class="table-primary">
                                                    <th>#</th>
                                                    <th>Date</th>
                                                    <th>Reference No.</th>
                                                    <th>Service Type</th>
                                                    <th>Description</th>
                                                    <th>Amount</th>
                                                    <th>Payer Name</th>
                                                    <th>Payer Email</th>
                                                    <th>Payer Phone</th>
                                                    <th class="text-center">Status</th>
                                                    <th class="text-center">Receipt</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($transactions as $data)
                                                    <tr>
                                                        <td>{{ ($transactions->currentPage() - 1) * $transactions->perPage() + $loop->iteration }}
                                                        </td>
                                                        <td>{{ $data->created_at->format('d M Y') }}</td>
                                                        <td>
                                                            <a target="_blank"
                                                                href="{{ route('admin.reciept', $data->referenceId) }}">
                                                                {{ strtoupper($data->referenceId) }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $data->service_type }}</td>
                                                        <td>{{ $data->service_description }}</td>
                                                        <td>&#8358;{{ number_format($data->amount, 2) }}</td>
                                                        <td>{{ $data->payer_name }}</td>
                                                        <td>{{ $data->payer_email }}</td>
                                                        <td>{{ $data->payer_phone }}</td>
                                                        <td class="text-center">
                                                            <span
                                                                class="btn
                                                                {{ $data->status == 'Approved' ? 'btn-success' : ($data->status == 'Rejected' ? 'btn-danger' : 'btn-warning') }}">
                                                                {{ strtoupper($data->status) }}
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <a target="_blank"
                                                                href="{{ route('admin.reciept', $data->referenceId) }}"
                                                                class="btn btn-primary btn-sm">
                                                                <i class="bi bi-download"></i> Download
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>

                                        {{-- Pagination Links --}}
                                        <div class="mt-3">
                                            {{ $transactions->appends(request()->query())->links() }}
                                        </div>
                                    @else
                                        <center>
                                            <img width="65%" src="{{ asset('assets/images/no-transaction.gif') }}"
                                                alt="">
                                            <p class="text-center fw-semibold fs-15 mt-2">No Transaction Available!</p>
                                        </center>
                                    @endif
                                </div>

                            </div>


                        </div>
                    </div>
                </div>
            </div>


        </div>

    @endsection
    @push('scripts')
    @endpush
