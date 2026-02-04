@extends('layouts.base')
@section('title','Thanas')

@php
$breadcrumb = [['title'=>'Location Management'],['title'=>'Thanas']];
@endphp

@section('content')
<div class="row">
<div class="col-md-10 offset-md-1">

<x-alert />

<button class="btn btn-sm fw-bold btn-primary mb-3"
        data-bs-toggle="modal"
        data-bs-target="#add_modal">
New Thana
</button>

<div class="card">
<div class="card-body">
<x-table id="thanas_table">
<thead>
<tr class="fw-semibold">
<th>Name</th>
<th>Division</th>
<th>District</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>
<tbody>
@foreach($thanas as $thana)
<tr>
<td>{{ $thana->name }}</td>
<td>{{ $thana->division?->name }}</td>
<td>{{ $thana->district?->name }}</td>
<td>
{!! $thana->toggleButton(
route('thanas.update-status',[
'thana'=>$thana->id,
'status'=>$thana->is_active?0:1
])
) !!}
</td>
<td>
<a class="btn btn-light-primary btn-icon btn-sm"
data-bs-toggle="modal"
data-bs-target="#update_modal_{{ $thana->id }}">
<i class="fa fa-edit"></i>
</a>

<button class="btn btn-light-danger btn-icon btn-sm"
onclick="confirmDelete('{{ $thana->id }}')">
<i class="fa fa-trash"></i>
</button>

<form id="{{ $thana->id }}"
method="POST"
action="{{ route('thanas.destroy',$thana->id) }}">
@csrf
@method('DELETE')
</form>
</td>
</tr>

{{-- Update Modal --}}
<x-modal id="update_modal_{{ $thana->id }}" title="Update Thana">
<form method="POST"
action="{{ route('thanas.update',$thana->id) }}">
@csrf
@method('PUT')

<div class="modal-body">

<select class="form-select division_update"
data-target="district_update_{{ $thana->id }}"
name="division_id" required>
@foreach($divisions as $division)
<option value="{{ $division->id }}"
{{ $division->id==$thana->division_id?'selected':'' }}>
{{ $division->name }}
</option>
@endforeach
</select>

<select class="form-select mt-2"
id="district_update_{{ $thana->id }}"
name="district_id" required>
@foreach($districts as $district)
@if($district->division_id==$thana->division_id)
<option value="{{ $district->id }}"
{{ $district->id==$thana->district_id?'selected':'' }}>
{{ $district->name }}
</option>
@endif
@endforeach
</select>

<input type="text"
name="name"
class="form-control mt-2"
value="{{ $thana->name }}"
required>

</div>
<div class="modal-footer">
<button class="btn btn-secondary"
data-bs-dismiss="modal">Close</button>
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
<x-modal id="add_modal" title="Create Thana">
<form method="POST" action="{{ route('thanas.store') }}">
@csrf
<div class="modal-body">

<select name="division_id"
id="division"
class="form-select" required>
<option value="">Select Division</option>
@foreach($divisions as $division)
<option value="{{ $division->id }}">
{{ $division->name }}
</option>
@endforeach
</select>

<select name="district_id"
id="district"
class="form-select mt-2" required>
<option value="">Select District</option>
</select>

<input type="text"
name="name"
class="form-control mt-2"
placeholder="Thana name" required>

<select name="is_active"
class="form-select mt-2">
<option value="1">Active</option>
<option value="0">Inactive</option>
</select>

</div>
<div class="modal-footer">
<button class="btn btn-secondary"
data-bs-dismiss="modal">Close</button>
<button class="btn btn-primary">Save</button>
</div>
</form>
</x-modal>

</div>
</div>
@endsection

@push('scripts')
<script>
// CREATE
$('#division').change(function(){
    let id = $(this).val();
    $('#district').html('<option>Loading...</option>');

    $.get("{{ url('get-districts') }}/"+id,function(data){
        let html = '<option value="">Select District</option>';
        data.forEach(d=>{
            html+=`<option value="${d.id}">${d.name}</option>`;
        });
        $('#district').html(html);
    });
});

// UPDATE
$('.division_update').change(function(){
    let id = $(this).val();
    let target = $(this).data('target');
    $('#'+target).html('<option>Loading...</option>');

    $.get("{{ url('get-districts') }}/"+id,function(data){
        let html='';
        data.forEach(d=>{
            html+=`<option value="${d.id}">${d.name}</option>`;
        });
        $('#'+target).html(html);
    });
});
</script>
@endpush
