@extends('layouts.base')
@section('title', 'Areas')

@php
    $breadcrumb = [['title' => 'Areas Management'], ['title' => 'Areas']];
@endphp

@section('content')
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <x-alert />

            <div class="mb-2">
                {{-- <a href="{{ route('vms.vehicle-requisitions.create') }}" class="btn btn-sm fw-bold btn-primary mb-3">New Vehicle</a> --}}
                <button type="button" class="btn btn-sm fw-bold btn-primary mb-3" data-bs-toggle="modal"
                    data-bs-target="#add_modal">New Area</button>
                <a href="#" data-bs-toggle="modal"
                    data-bs-target="#bulk_upload_modal" class="btn btn-sm fw-bold btn-primary mb-3">Bulk Upload</a>
            </div>

            <div class="card">
                <div class="card-body">
                    <x-table class="" id="areas_table">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th>Name</th>
                                <th>Region</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($areas as $area)
                                <tr>
                                    <td>{{ $area->name }} </td>
                                    <td>{{ $area->region?->name }} </td>
                                    <td>
                                        {!! $area->toggleButton(
                                            route('areas.update-status', [
                                                'area' => $area->id,
                                                'status' => $area->is_active == 1 ? 0 : 1,
                                            ])
                                        ) !!}
                                    </td>

                                    <td>
                                        <a href="#" class="btn btn-light-primary btn-icon btn-sm me-2" data-bs-toggle="modal"
                                            data-bs-target="#update_modal_{{ $area->id }}"><i class="fa fa-edit"></i></a>

                                        <button type="button" class="btn btn-sm btn-light-danger btn-icon"
                                            onclick="confirmDelete('{{ $area->id }}')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        <form method="POST"
                                            action="{{ route('areas.destroy', ['area' => $area->id]) }}"
                                            id="{{ $area->id }}">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>

                                <x-modal id="update_modal_{{ $area->id }}" title="Update Area Info">
                                    <form action="{{ route('areas.update', ['area'=> $area->id]) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body">
                                            <x-form-group>
                                                <x-form-label for="name_{{ $area->id }}" required="true">Name</x-form-label>
                                                <input type="text" name="name" id="name_{{ $area->id }}" class="form-control"
                                                    value="{{ $area->name }}" required />
                                            </x-form-group>

                                            <x-form-group>
                                                <x-form-label for="region_{{ $area->id }}" required="true">Region</x-form-label>
                                                <select name="region_id" id="region_id_update_{{ $area->id }}" class="form-select js-select2" required>
                                                    @foreach ($regions as $region)
                                                        <option value="{{ $region->id }}" {{ $area->region_id == $region->id ? 'selected' : '' }}>
                                                            {{ $region->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </x-form-group>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Update</button>
                                        </div>
                                    </form>
                                </x-modal>
                            @endforeach
                        </tbody>
                    </x-table>
                </div>
            </div>


            <x-modal id="add_modal" title="Create Area">
                <form action="{{ route('areas.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <x-form-group>
                            <x-form-label for="name" required="true">Name</x-form-label>
                            <input type="text" name="name" id="name" class="form-control"
                                value="{{ old('name') }}" required />
                        </x-form-group>

                        <x-form-group>
                            <x-form-label for="region_id" required="true">Region</x-form-label>
                            <select name="region_id" id="region_id" class="form-select js-select2" required>
                                @foreach ($regions as $region)
                                    <option value="{{ $region->id }}" {{ old('region_id') == $region->id ? 'selected' : '' }}>
                                        {{ $region->name }}
                                    </option>
                                @endforeach
                            </select>
                        </x-form-group>

                        <x-form-group>
                            <x-form-label for="is_active" required="true">Status</x-form-label>
                            <select name="is_active" id="is_active" class="form-select" required>
                                <option value="0" {{ old('is_active', 1) == 0 ? 'selected' : '' }}>Inactive</option>
                                <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Active</option>
                            </select>
                        </x-form-group>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </x-modal>


            <x-modal id="bulk_upload_modal" title="Bulk Upload Area">
                <form action="{{ route('areas.bulk-upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <x-form-group>
                            <a href="{{ asset('import-sample/area_bulk_upload_example.xlsx') }}" target="_blank">Download Excel Format</a>
                        </x-form-group>

                        <x-form-group>
                            <x-form-label for="file" required="true">File</x-form-label>
                            <input type="file" name="file" id="file" class="form-control" required />
                            <small class="text-muted">Only CSV, XLSX, XLS files are allowed</small>
                        </x-form-group>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </x-modal>

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(async function() {
            $(".js-select2").each(function() {
                $(this).select2({
                    dropdownParent: $(this).parent(),
                });
            });
        })
    </script>
@endpush
