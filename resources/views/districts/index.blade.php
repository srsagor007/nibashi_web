@extends('layouts.base')
@section('title', 'Districts')

@php
    $breadcrumb = [['title' => 'Districts Management'], ['title' => 'Districts']];
@endphp

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <x-alert />

        <button class="btn btn-sm fw-bold btn-primary mb-3" data-bs-toggle="modal"
                data-bs-target="#add_modal">
            New District
        </button>

        <div class="card">
            <div class="card-body">
                <x-table id="districts_table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Division</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($districts as $district)
                        <tr>
                            <td>{{ $district->name }}</td>
                            <td>{{ $district->division?->name }}</td>
                            <td>
                                {!! $district->toggleButton(
                                    route('districts.update-status', [
                                        'district' => $district->id,
                                        'status' => $district->is_active ? 0 : 1
                                    ])
                                ) !!}
                            </td>
                            <td>
                                <a class="btn btn-light-primary btn-sm"
                                   data-bs-toggle="modal"
                                   data-bs-target="#update_modal_{{ $district->id }}">
                                   <i class="fa fa-edit"></i>
                                </a>

                                <button class="btn btn-light-danger btn-sm"
                                        onclick="confirmDelete('{{ $district->id }}')">
                                    <i class="fa fa-trash"></i>
                                </button>

                                <form id="{{ $district->id }}"
                                      action="{{ route('districts.destroy', $district->id) }}"
                                      method="POST">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>

                        {{-- Update Modal --}}
                        <x-modal id="update_modal_{{ $district->id }}" title="Update District">
                            <form action="{{ route('districts.update', $district->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <x-form-group>
                                        <x-form-label>Name</x-form-label>
                                        <input type="text" name="name"
                                               value="{{ $district->name }}"
                                               class="form-control" required>
                                    </x-form-group>

                                    <x-form-group>
                                        <x-form-label>Division</x-form-label>
                                        <select name="division_id" class="form-select" required>
                                            @foreach ($divisions as $division)
                                                <option value="{{ $division->id }}"
                                                    {{ $district->division_id == $division->id ? 'selected' : '' }}>
                                                    {{ $division->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </x-form-group>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button class="btn btn-primary">Update</button>
                                </div>
                            </form>
                        </x-modal>
                        @endforeach
                    </tbody>
                </x-table>
            </div>
        </div>

        {{-- Create Modal --}}
        <x-modal id="add_modal" title="Create District">
            <form action="{{ route('districts.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <x-form-group>
                        <x-form-label>Name</x-form-label>
                        <input type="text" name="name" class="form-control" required>
                    </x-form-group>

                    <x-form-group>
                        <x-form-label>Division</x-form-label>
                        <select name="division_id" class="form-select" required>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </x-form-group>

                    <x-form-group>
                        <x-form-label>Status</x-form-label>
                        <select name="is_active" class="form-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </x-form-group>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </x-modal>
    </div>
</div>
@endsection
