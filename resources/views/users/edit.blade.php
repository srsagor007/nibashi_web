@extends('layouts.base')
@section('title', 'User Edit')
@php
    $breadcrumb = [
        ['title' => 'Users'],
        ['title' => 'Manage', 'url' => route('users.index')],
        ['title' => 'User Edit'],
    ];
@endphp


@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <x-alert :show_validations="false" />

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('users.update', ['user' => $user->id]) }}" method="POST" id="user_form" enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        <div class="col-md-12 mb-4 text-center">
                            <div class="image-input image-input-circle mb-3" data-kt-image-input="true">
                                <div class="image-input-wrapper w-125px h-125px border border-2 border-secondary"
                                    style="background-image: url('{{ $user->photo_url ?? asset('img/blank-avatar-profile.webp') }}')"></div>
                                <label class="btn btn-icon btn-circle btn-outline-primary w-25px h-25px shadow"
                                    data-kt-image-input-action="change"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Change photo">
                                    <i class="fas fa-pencil-alt fs-6"></i>
                                    <input type="file" name="photo" accept=".png, .jpg, .jpeg, .webp" />
                                    <input type="hidden" name="photo_remove" />
                                </label>
                                <span class="btn btn-icon btn-circle btn-outline-secondary w-25px h-25px shadow"
                                    data-kt-image-input-action="cancel"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Cancel photo">
                                    <i class="fas fa-times fs-3"></i>
                                </span>
                                <span class="btn btn-icon btn-circle btn-outline-danger w-25px h-25px shadow"
                                    data-kt-image-input-action="remove"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Remove photo">
                                    <i class="fas fa-trash-alt fs-3"></i>
                                </span>
                            </div>
                            <p class="text-muted mt-2 mb-0">
                                <small>Upload JPG, PNG, JPEG, WEBP format(Max 2MB)</small>
                                <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                            </p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label required="true" for="name">Name</x-form-label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        placeholder="Name" value="{{ $user->name }}" required />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </x-form-group>
                            </div>
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="designation" required="true">Designation</x-form-label>
                                    <input type="text" name="designation" id="designation" class="form-control"
                                        placeholder="Designation" value="{{ $user->designation }}" required />
                                    <x-input-error :messages="$errors->get('designation')" class="mt-2" />
                                </x-form-group>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label required="true" for="userid">Employee ID <small>(Login
                                            ID)</small></x-form-label>
                                    <input type="text" name="userid" id="userid" class="form-control form-control-solid"
                                        placeholder="RFXXXX" value="{{ $user->userid }}" required readonly />
                                    <x-input-error :messages="$errors->get('userid')" class="mt-2" />
                                </x-form-group>
                            </div>
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="phone" required="true">Phone</x-form-label>
                                    <input type="number" name="phone" minlength="11" maxlength="11" id="phone"
                                        class="form-control" placeholder="01XXXXXXXXX" value="{{ $user->phone }}" />
                                    <small class="text-info">N.B: 11 digit phone no</small>
                                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                                </x-form-group>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="tbl_business_business_code" required="true">Business</x-form-label>
                                    <select name="tbl_business_business_code" id="tbl_business_business_code"
                                        class="form-select" required>
                                        <option value="">-Select Business-</option>
                                        @foreach ($business_units as $business)
                                            <option value="{{ $business->business_code }}"
                                                {{ selected($business->business_code, $user->tbl_business_business_code) }}>
                                                {{ $business->name_with_code }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('tbl_business_business_code')" class="mt-2" />
                                </x-form-group>
                            </div>
                            <div class="col-md-6">
                                <x-form-group>
                                    <x-form-label for="primary_role_id" required="true">User Type</x-form-label>
                                    <select name="primary_role_id" id="primary_role_id" class="form-select" required>
                                        <option value="">-Select User type-</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}" data-role-slug="{{ $role->slug }}"
                                                {{ selected($role->id, $user->primary_role_id) }}>
                                                {{ $role->title }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('primary_role_id')" class="mt-2" />
                                </x-form-group>
                            </div>
                        </div>

                        <div class="d-none" id="conditional_area">
                            <div class="row">
                                <div class="col-md-6">
                                    <x-form-group>
                                        <x-form-label for="tbl_depot_id">Depot</x-form-label>
                                        <select name="tbl_depot_id" id="tbl_depot_id" class="form-select">
                                            <option value="">-Select depot-</option>
                                            @foreach ($depots as $depot)
                                                <option value="{{ $depot->id }}"
                                                    {{ selected($depot->id, $user->tbl_depot_id) }}>
                                                    {{ $depot->name_with_code }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('tbl_depot_id')" class="mt-2" />
                                    </x-form-group>
                                </div>

                                <div class="col-md-6" id="user_code_area">
                                    <x-form-group>
                                        <x-form-label for="user_code" id="user_code_label">User Code</x-form-label>
                                        <input type="text" name="user_code" id="user_code" class="form-control"
                                            value="{{ $user->user_code }}" />
                                        <x-input-error :messages="$errors->get('user_code')" class="mt-2" />
                                    </x-form-group>
                                </div>
                            </div>

                            <div class="row d-none" id="dsm_area">
                                <div class="col-md-6" id="dsm_type_area">
                                    <x-form-group>
                                        <x-form-label for="tbl_pso_user_type_id">DSM Type</x-form-label>
                                        <select name="tbl_pso_user_type_id" id="tbl_pso_user_type_id" class="form-select">
                                            <option value="">-Select depot-</option>
                                            @foreach ($pso_user_types as $pso_user_type)
                                                <option value="{{ $pso_user_type->id }}"
                                                    {{ selected($pso_user_type->id, $user->tbl_pso_user_type_id) }}>
                                                    {{ $pso_user_type->pso_user_type_name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('tbl_pso_user_type_id')" class="mt-2" />
                                    </x-form-group>
                                </div>

                                <div class="col-md-6">
                                    <x-form-group>
                                        <x-form-label for="parent_rsm_code">RSM Code</x-form-label>
                                        <input type="text" name="parent_rsm_code" id="parent_rsm_code"
                                            class="form-control" placeholder="Parent RSM Code"
                                            value="{{ $user->supervisor_user_code }}" />
                                        <x-input-error :messages="$errors->get('parent_rsm_code')" class="mt-2" />
                                    </x-form-group>
                                </div>
                            </div>

                            <div class="row d-none" id="rsm_area">
                                <div class="col-md-6">
                                    <x-form-group>
                                        <x-form-label for="rsm_region_id" required="true">RSM Region</x-form-label>
                                        <select name="rsm_region_id" id="rsm_region_id" class="form-control">
                                            <option value="">-Select Region-</option>
                                            @foreach ($regions as $region)
                                                <option value="{{ $region->id }}" {{ selected($region->id, $user->rsm_region_id) }}>{{ $region->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('rsm_region_id')" class="mt-2" />
                                    </x-form-group>
                                </div>

                                <div class="col-md-6">
                                    <x-form-group>
                                        <x-form-label for="parent_sm_code">SM Code</x-form-label>
                                        <input type="text" name="parent_sm_code" id="parent_sm_code"
                                            class="form-control" placeholder="Parent SM Code"
                                            value="{{ $user->supervisor_user_code }}" />
                                        <x-input-error :messages="$errors->get('parent_sm_code')" class="mt-2" />
                                    </x-form-group>
                                </div>

                                <div class="col-md-6">
                                    <x-form-group>
                                        <x-form-label for="buddy_name">Buddy Name</x-form-label>
                                        <input type="text" name="buddy_name" id="buddy_name" class="form-control"
                                            placeholder="Buddy name" value="{{ $user->buddy_info ? $user->buddy_info->buddy_name : '' }}" />
                                        <x-input-error :messages="$errors->get('buddy_name')" class="mt-2" />
                                    </x-form-group>
                                </div>

                                <div class="col-md-6">
                                    <x-form-group>
                                        <x-form-label for="buddy_phone">Buddy Phone</x-form-label>
                                        <input type="text" name="buddy_phone" id="buddy_phone" class="form-control"
                                            placeholder="Buddy phone" value="{{ $user->buddy_info ? $user->buddy_info->buddy_phone : '' }}" />
                                        <x-input-error :messages="$errors->get('buddy_phone')" class="mt-2" />
                                    </x-form-group>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 border-top pt-5">
                            <button type="submit" class="btn btn-primary" id="createBtn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(async function() {
            $("#tbl_depot_id").select2();

            toggle_input_areas();
        })

        $('#primary_role_id').change(function() {
            toggle_input_areas();
        })

        function toggle_input_areas() {
            var role_slug = $('#primary_role_id').find(':selected').data('role-slug')

            if (['dsm', 'rsm', 'sm'].includes(role_slug)) {
                $('#conditional_area').removeClass('d-none');
                $('#tbl_depot_id').attr('required', true);
                $('#user_code').attr('required', true);
            } else {
                reset_input_areas();
                return; // Exit early if role_slug is not relevant
            }
            reset_input_areas()

            switch (role_slug) {
                case 'dsm':
                    show_dsm_area();
                    break;
                case 'rsm':
                    show_rsm_area();
                    break;
                case 'sm':
                    show_sm_area();
                    break;
            }
        }

        function reset_input_areas() {
            $('#conditional_area, #dsm_area, #rsm_area').addClass('d-none');
            $('#tbl_depot_id, #user_code, #tbl_pso_user_type_id, #parent_rsm_code').removeAttr('required');

            $('#user_code_label').html('User Code');
        }

        function show_dsm_area() {
            $('#conditional_area, #dsm_area').removeClass('d-none');
            $('#tbl_pso_user_type_id, #parent_rsm_code').attr('required', true);
            $('#user_code_label').html(`DSM Code <span class="text-danger">*</span>`)
        }

        function show_rsm_area() {
            $('#conditional_area, #rsm_area').removeClass('d-none');
            $('#user_code_label').html(`RSM Code <span class="text-danger">*</span>`)
        }

        function show_sm_area() {
            $('#conditional_area').removeClass('d-none');
            $('#user_code_label').html(`SM Code <span class="text-danger">*</span>`)
        }
    </script>
@endpush
