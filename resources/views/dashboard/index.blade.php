@extends('layouts.base')

@section('title', 'Dashboard')

@section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6 mt-3">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    Welcome, <br>{{ auth()->user()->name_with_userid }}</h1>
            </div>

            {{-- @if (auth()->user()->hasPermission('vms/vehicle_requisition_controller@create'))
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <a href="#" class="btn btn-sm fw-bold btn-info">New</a>
                </div>
            @endif --}}
        </div>
    </div>


@endsection

@push('scripts')
<script>
    $(document).ready(function () {

    });

</script>
@endpush
