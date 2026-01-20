@extends('layouts.base')
@section('title', 'Admin Console | Menus')

@php
    $breadcrumb = [
        ['title'=> 'Admin Console'],
        ['title'=> 'Settings', 'url' => route('settings.index')]
    ]
@endphp

@section('content')
<div class="row">

    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs nav-pills flex-row border-0 flex-md-column me-5 mb-3 mb-md-0 fs-6 min-w-lg-200px">
                    <li class="nav-item w-100 me-0 mb-md-2">
                        <a class="nav-link w-100 active btn btn-flex btn-active-light-primary" data-bs-toggle="tab" href="#kt_tab_pane_1">
                            <i class="fa fa-home fs-3 me-2"></i>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fs-4 fw-bold">General Settings</span>
                                <span class="fs-7">All general settings goes here</span>
                            </span>
                        </a>
                    </li>
                    {{-- <li class="nav-item w-100 me-0 mb-md-2">
                        <a class="nav-link w-100 btn btn-flex btn-active-light-primary" data-bs-toggle="tab" href="#kt_tab_pane_2">
                            <i class="fa fa-cog fs-3 me-2"></i>
                            <span class="d-flex flex-column align-items-start">
                                <span class="fs-4 fw-bold">Bulk SMS & Email</span>
                                <span class="fs-7">Bulk SMS & Email Credentials</span>
                            </span>
                        </a>
                    </li> --}}

                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        {{-- <x-alert /> --}}

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="kt_tab_pane_1" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-body">
                        <h3 class="border-bottom border-gray-200 pb-4 mb-4">General Settings</h3>

                        <form action="{{ route('settings.update-site-info') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row fv-row mb-3 fv-plugins-icon-container">
                                <div class="col-md-3 text-md-ends">
                                    <label for="site_title" class="fs-6 fw-semibold form-label mt-3">Site Title</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control form-control-solid" 
                                        id="site_title" name="site_title" value="{{ settings('site_info', 'site_title') }}" placeholder="Title">
                                    <x-input-error :messages="$errors->get('site_title')" />
                                </div>
                            </div>
    
                            <div class="row fv-row mb-3 fv-plugins-icon-container">
                                <div class="col-md-3 text-md-ends">
                                    <label for="site_logo" class="fs-6 fw-semibold form-label mt-3">Site Logo</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="file" class="form-control form-control-solid" id="site_logo" name="site_logo" placeholder="Site Logo">
                                    <x-input-error :messages="$errors->get('site_logo')" />
                                    @if(settings('site_info', 'site_logo'))
                                        <a href="{{ asset('storage/' . settings('site_info', 'site_logo')) }}" class="ms-2">Click to view logo</a>
                                    @endif
                                </div>
                            </div>
    
                            <div class="mt-3">
                                <input type="submit" name="" id="" class="btn btn-primary" value="Update">
                            </div>
                        </form>                            
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="kt_tab_pane_2" role="tabpanel">
                <div class="card mb-3">
                    <div class="card-body">
                        <h3 class="border-bottom border-gray-200 pb-4 mb-4">SMS Configs</h3>
                        <form action="">
                            @csrf
                            @method('PUT')
                            <div class="row fv-row mb-3 fv-plugins-icon-container">
                                <div class="col-md-3 text-md-ends">
                                    <label for="sms_api_url" class="fs-6 fw-semibold form-label mt-3">SMS API url</label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control form-control-solid" id="sms_api_url" name="sms_api_url" placeholder="SMS API URL">
                                    <div class="fv-plugins-message-container invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <input type="submit" name="" id="" class="btn btn-primary" value="Update">
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h3 class="border-bottom border-gray-200 pb-4 mb-4">Mail Configs</h3>
                        <div class="row fv-row mb-3 fv-plugins-icon-container">
                            <div class="col-md-3 text-md-ends">
                                <label for="" class="fs-6 fw-semibold form-label mt-3">Mail Host</label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" class="form-control form-control-solid" name="mail_host" placeholder="Ex: smtp-mail.outlook.com">
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="row fv-row mb-3 fv-plugins-icon-container">
                            <div class="col-md-3 text-md-ends">
                                <label for="" class="fs-6 fw-semibold form-label mt-3">Mail Port</label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" class="form-control form-control-solid" name="mail_host" placeholder="Ex: 587">
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="row fv-row mb-3 fv-plugins-icon-container">
                            <div class="col-md-3 text-md-ends">
                                <label for="" class="fs-6 fw-semibold form-label mt-3">Mail Username</label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" class="form-control form-control-solid" name="mail_host" placeholder="Ex: example@example.com">
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="row fv-row mb-3 fv-plugins-icon-container">
                            <div class="col-md-3 text-md-ends">
                                <label for="" class="fs-6 fw-semibold form-label mt-3">Mail Password</label>
                            </div>
                            <div class="col-md-9">
                                <input type="password" class="form-control form-control-solid" name="mail_host" placeholder="Password">
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="row fv-row mb-3 fv-plugins-icon-container">
                            <div class="col-md-3 text-md-ends">
                                <label for="" class="fs-6 fw-semibold form-label mt-3">Mail Encryption</label>
                            </div>
                            <div class="col-md-9">
                                <select name="" id="" class="form-select form-select-solid">
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                </select>
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="row fv-row mb-3 fv-plugins-icon-container">
                            <div class="col-md-3 text-md-ends">
                                <label for="" class="fs-6 fw-semibold form-label mt-3">Mail From Address</label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" class="form-control form-control-solid" name="mail_host" placeholder="Ex: example@example.com">
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="row fv-row mb-3 fv-plugins-icon-container">
                            <div class="col-md-3 text-md-ends">
                                <label for="" class="fs-6 fw-semibold form-label mt-3">Mail From Name</label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" class="form-control form-control-solid" name="mail_host" placeholder="Ex: XYZ Company">
                                <div class="fv-plugins-message-container invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <input type="submit" name="" id="" class="btn btn-primary" value="Update">
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection
