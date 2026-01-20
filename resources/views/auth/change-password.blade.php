@extends('layouts.guest')

@section('content')


    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h2 class="fw-bold mb-0">Change Your Password</h2>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-info px-4">
                    Log Out
                </button>
            </form>
        </div>
    </div>
    
    <form method="POST" action="{{ route('password-change.submit') }}" enctype="multipart/form-data">
        @csrf 
        <div class="row mb-5">
            <div class="col-12">
                <x-alert />

                <div class="fv-row mb-5">
                    <label for="current_password" class="form-label fs-6 fw-bold mb-3">Current Password</label>
                    <input type="password" class="form-control form-control-lg form-control-solid"
                        minlength="6" name="current_password" id="current_password" required />
                </div>
            </div>
            
            <div class="col-12">
                <div class="fv-row mb-5">
                    <label for="password" class="form-label fs-6 fw-bold mb-2">New Password</label>
                    <input type="password" class="form-control form-control-lg form-control-solid"
                        minlength="6" name="password" id="password" required />
                </div>
            </div>

            <div class="col-12">
                <div class="fv-row mb-5">
                    <label for="password_confirmation" class="form-label fs-6 fw-bold mb-2">Confirm New Password</label>
                    <input type="password" class="form-control form-control-lg form-control-solid"
                        minlength="6" name="password_confirmation" id="password_confirmation" required />
                </div>
            </div>
        </div>

        <div class="form-text mb-5">Password must be at least 6 characters</div>

        <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary px-4">
                Update Password
            </button>
        </div>
    </form>
@endsection

