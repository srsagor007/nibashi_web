@extends('layouts.base')
@section('title', 'Divisions')

@php
    $breadcrumb = [['title' => 'Divisions Management'], ['title' => 'Divisions']];
@endphp

@section('content')
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <x-alert />

            <div class="mb-2">
                <button type="button" class="btn btn-sm fw-bold btn-primary mb-3" data-bs-toggle="modal"
                    data-bs-target="#add_modal">New Division</button>
            </div>

            <div class="card">
                <div class="card-body">
                    <x-table id="divisions_table">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th>Name</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($divisions as $division)
                                <tr>
                                    <td>{{ $division->name }}</td>
                                    <td>
                                        {!! $division->toggleButton(
                                            route('divisions.update-status', [
                                                'division' => $division->id,
                                                'status' => $division->is_active == 1 ? 0 : 1,
                                            ])
                                        ) !!}
                                    </td>

                                    <td>
                                        <a href="#" class="btn btn-light-primary btn-icon btn-sm me-2"
                                           data-bs-toggle="modal"
                                           data-bs-target="#update_modal_{{ $division->id }}">
                                            <i class="fa fa-edit"></i>
                                        </a>

                                        <button type="button" class="btn btn-sm btn-light-danger btn-icon"
                                            onclick="confirmDelete('{{ $division->id }}')">
                                            <i class="fa fa-trash"></i>
                                        </button>

                                        <form method="POST"
                                            action="{{ route('divisions.destroy', ['division' => $division->id]) }}"
                                            id="{{ $division->id }}">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>

                                {{-- Update Modal --}}
                                <x-modal id="update_modal_{{ $division->id }}" title="Update Division">
                                    <form action="{{ route('divisions.update', ['division'=> $division->id]) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body">
                                            <x-form-group>
                                                <x-form-label for="name_{{ $division->id }}" required="true">
                                                    Name
                                                </x-form-label>
                                                <input type="text" name="name"
                                                       id="name_{{ $division->id }}"
                                                       class="form-control"
                                                       value="{{ $division->name }}" required />
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

            {{-- Create Modal --}}
            <x-modal id="add_modal" title="Create Division">
                <form action="{{ route('divisions.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <x-form-group>
                            <x-form-label for="name" required="true">Name</x-form-label>
                            <input type="text" name="name" id="name"
                                   class="form-control"
                                   value="{{ old('name') }}" required />
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

        </div>
    </div>
@endsection
