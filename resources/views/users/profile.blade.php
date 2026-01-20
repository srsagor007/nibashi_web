@extends('layouts.base')
@section('title', 'Profile')

@section('content')
    <div class="row">
        <div class="col-md-8 offset-2">

            <div class="card mb-5 mb-xl-10">
                <div class="card-body pt-9 pb-0">
                    <div class="d-flex flex-wrap flex-sm-nowrap">
                        <div class="me-7 mb-4">
                            <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                                <img src="{{ $user->photo_url }}" alt="image" />
                                <div
                                    class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-{{ $user->is_active ? 'success' : 'danger' }} rounded-circle border border-4 border-body h-20px w-20px">
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                                <div class="d-flex flex-column">
                                    <div class="d-flex align-items-center mb-2">
                                        <a href="#"
                                            class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">{{ $user->name }}</a>
                                    </div>
                                    <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                        <a href="#"
                                            class="d-flex align-items-center text-primary-400 me-5 mb-2">#{{ $user->userid }}</a>
                                        </div>
                                        <h4 class="d-flex align-items-center text-muted me-5 mb-2">
                                            Role: 
                                            @if(auth()->user()->is_superuser)
                                            Super Admin
                                            @elseif(session('role'))
                                            {{ session('role')->title }}
                                            @endif
                                        </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-5 mb-xl-10">
                <div class="card-header border-0 cursor-pointer" role="button">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Reset Password</h3>
                    </div>
                </div>
                <div class="card-body border-top p-9">
                    <div class="d-flex flex-wrap align-items-center mb-10">
                        <div class="flex-row-fluid">
                            <form action="{{ route('users.reset-password', ['id' => $user->id]) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="row mb-1">
                                    <div class="col-lg-4">
                                        <div class="fv-row mb-0">
                                            <label for="current_password" class="form-label fs-6 fw-bold mb-3">Current
                                                Password</label>
                                            <input type="password" class="form-control form-control-lg form-control-solid"
                                                minlength="8" name="current_password" id="current_password" required />
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="fv-row mb-0">
                                            <label for="password" class="form-label fs-6 fw-bold mb-3">New Password</label>
                                            <input type="password" class="form-control form-control-lg form-control-solid"
                                                minlength="8" name="password" id="password" required />
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="fv-row mb-0">
                                            <label for="password_confirmation" class="form-label fs-6 fw-bold mb-3">Confirm
                                                New Password</label>
                                            <input type="password" class="form-control form-control-lg form-control-solid"
                                                minlength="8" name="password_confirmation" id="password_confirmation"
                                                required />
                                        </div>
                                    </div>
                                </div>
                                <div class="form-text mb-5">Password must be at least 8 character</div>
                                <div class="d-flex">
                                    <button type="submit" class="btn btn-primary me-2 px-6">Update Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
