@extends('layouts.base')
@section('title','Areas')

@section('content')
<div class="row">
<div class="col-md-10 offset-md-1">

<x-alert />

<button class="btn btn-sm fw-bold btn-primary mb-3"
data-bs-toggle="modal"
data-bs-target="#add_modal">
New Area
</button>

<div class="card">
<div class="card-body">
<x-table>
<thead>
<tr>
<th>Name</th>
<th>Division</th>
<th>District</th>
<th>Thana</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>
<tbody>
@foreach($areas as $area)
<tr>
<td>{{ $area->name }}</td>
<td>{{ $area->division->name }}</td>
<td>{{ $area->district->name }}</td>
<td>{{ $area->thana->name }}</td>
<td>
{!! $area->toggleButton(
route('areas.update-status',[
'area'=>$area->id,
'status'=>$area->is_active?0:1
])
) !!}
</td>
<td>
<button class="btn btn-danger btn-sm"
onclick="confirmDelete('{{ $area->id }}')">
Delete
</button>
<form id="{{ $area->id }}"
method="POST"
action="{{ route('areas.destroy',$area->id) }}">
@csrf
@method('DELETE')
</form>
</td>
</tr>
@endforeach
</tbody>
</x-table>
</div>
</div>

{{-- Create Modal --}}
<x-modal id="add_modal" title="Create Area">
<form method="POST" action="{{ route('areas.store') }}">
@csrf
<div class="modal-body">

<select id="division"
name="division_id"
class="form-select" required>
<option value="">Select Division</option>
@foreach($divisions as $division)
<option value="{{ $division->id }}">{{ $division->name }}</option>
@endforeach
</select>

<select id="district"
name="district_id"
class="form-select mt-2" required>
<option value="">Select District</option>
</select>

<select id="thana"
name="thana_id"
class="form-select mt-2" required>
<option value="">Select Thana</option>
</select>

<input type="text"
name="name"
class="form-control mt-2"
placeholder="Area name" required>

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
$('#division').change(function(){
    let id = $(this).val();
    $('#district').html('<option>Loading...</option>');
    $('#thana').html('<option>Select Thana</option>');

    $.get("{{ url('get-districts') }}/"+id,function(data){
        let html = '<option value="">Select District</option>';
        data.forEach(d=>{
            html+=`<option value="${d.id}">${d.name}</option>`;
        });
        $('#district').html(html);
    });
});

$('#district').change(function(){
    let id = $(this).val();
    $('#thana').html('<option>Loading...</option>');

    $.get("{{ url('get-thanas') }}/"+id,function(data){
        let html = '<option value="">Select Thana</option>';
        data.forEach(t=>{
            html+=`<option value="${t.id}">${t.name}</option>`;
        });
        $('#thana').html(html);
    });
});
</script>
@endpush
