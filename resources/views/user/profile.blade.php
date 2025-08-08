@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="mb-3 mt-1">
        <h4 class="mb-1">Welcome back, {{ auth()->user()->name ?? 'User' }} 👋</h4>
        <p class="mb-0">Here’s a quick look at your dashboard.</p>
    </div>

    @include('common.message')

    <div class="col-lg-12 grid-margin d-flex flex-column">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card p-4">
                    <div class="row align-items-center">
                        <!-- Profile Image or Icon -->
                        <div class="col-md-4 text-center">
                            @if(Auth::user()->profile_pic)
                                <img src="data:image/jpeg;base64,{{ Auth::user()->profile_pic }}"
                                     alt="Profile Image"
                                     class="rounded-circle img-thumbnail"
                                     style="width: 130px; height: 130px; object-fit: cover;">
                            @else
                                <i class="bi bi-person-circle bg-primary rounded-circle"
                                   style="font-size: 3rem; color: #fff; padding: 20px;"></i>
                            @endif
                            <h5 class="mt-5">{{ Auth::user()->name }}</h5>
                            <p class="text-muted">{{ Auth::user()->email }}</p>
                        </div>

                        <!-- Profile Update Form -->
                        <div class="col-md-8">
                            <form action="{{ route('user.profile.update') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <!-- Upload Image -->
                                <div class="form-group mb-3">
                                    <label for="profile_image">Profile Image</label>
                                    <input type="file" class="form-control" name="profile_pic" id="profile_pic" accept="image/*">
                                    @error('profile_image')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <!-- New Password -->
                                <div class="form-group mb-3">
                                    <label for="password">New Password</label>
                                    <input type="password" class="form-control" name="password" id="password">
                                    @error('password')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <!-- Confirm Password -->
                                <div class="form-group mb-3">
                                    <label for="password_confirmation">Confirm Password</label>
                                    <input type="password" class="form-control" name="password_confirmation" id="password_confirmation">
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                            </form>
                        </div>
                    </div> <!-- end row -->
                </div> <!-- end card -->
            </div>
        </div>
    </div>
</div>
@endsection
