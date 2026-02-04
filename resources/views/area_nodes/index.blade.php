@extends('layouts.base')
@section('title','Area Nodes')

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">

        <x-alert />

        {{-- Add Node Button --}}
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#add_modal">Add Node</button>

        {{-- Nodes Table --}}
        <div class="card">
            <div class="card-body">
                <x-table id="nodes_table">
                    <thead>
                        <tr>
                            <th>Full Path</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($nodes as $node)
                        <tr>
                            <td>{{ $node->fullPath() }}</td>
                            <td>{{ ucfirst($node->type) }}</td>
                            <td>
                                {!! $node->toggleButton(route('area-nodes.update-status',['areaNode'=>$node->id,'status'=>$node->is_active?0:1])) !!}
                            </td>
                            <td>
                                {{-- Edit --}}
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#edit_modal_{{ $node->id }}">Edit</button>
                                {{-- Delete --}}
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete('{{ $node->id }}')">Delete</button>
                                <form id="{{ $node->id }}" method="POST" action="{{ route('area-nodes.destroy',$node->id) }}">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>

                        {{-- Edit Modal --}}
                        <x-modal id="edit_modal_{{ $node->id }}" title="Edit Node">
                        <form method="POST" action="{{ route('area-nodes.update',$node->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">

                                <select id="area_edit_{{ $node->id }}" name="area_id" class="form-select area-select" required>
                                    <option value="">Select Area</option>
                                    @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ $node->area_id==$area->id?'selected':'' }}>{{ $area->name }}</option>
                                    @endforeach
                                </select>

                                <select id="type_edit_{{ $node->id }}" name="type" class="form-select type-select mt-2" required>
                                    <option value="">Select Type</option>
                                    <option value="block" {{ $node->type=='block'?'selected':'' }}>Block</option>
                                    <option value="sector" {{ $node->type=='sector'?'selected':'' }}>Sector</option>
                                    <option value="road" {{ $node->type=='road'?'selected':'' }}>Road</option>
                                </select>

                                <select id="parent_edit_{{ $node->id }}" name="parent_id" class="form-select mt-2" data-selected="{{ $node->parent_id ?? '' }}">
                                    <option value="">Select Parent (optional)</option>
                                </select>

                                <input type="text" name="name" class="form-control mt-2" placeholder="Node Name" value="{{ $node->name }}" required>

                                <select name="is_active" class="form-select mt-2">
                                    <option value="1" {{ $node->is_active? 'selected':'' }}>Active</option>
                                    <option value="0" {{ !$node->is_active? 'selected':'' }}>Inactive</option>
                                </select>

                            </div>
                            <div class="modal-footer">
                                {{-- <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button> --}}
                                <button class="btn btn-primary">Update</button>
                            </div>
                        </form>
                        </x-modal>

                        @endforeach
                    </tbody>
                </x-table>
            </div>
        </div>

        {{-- Add Modal --}}
        <x-modal id="add_modal" title="Add Node">
        <form method="POST" action="{{ route('area-nodes.store') }}">
            @csrf
            <div class="modal-body">

                <select id="area" name="area_id" class="form-select" required>
                    <option value="">Select Area</option>
                    @foreach($areas as $area)
                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </select>

                <select id="type" name="type" class="form-select mt-2" required>
                    <option value="">Select Type</option>
                    <option value="block">Block</option>
                    <option value="sector">Sector</option>
                    <option value="road">Road</option>
                </select>

                <select id="parent" name="parent_id" class="form-select mt-2">
                    <option value="">Select Parent (optional)</option>
                </select>

                <input type="text" name="name" class="form-control mt-2" placeholder="Node Name" required>

                <select name="is_active" class="form-select mt-2">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>

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

@push('scripts')
<script>
function loadParentNodes(area_id, type, parent_select_id, selected_parent_id = null){
    if(!area_id || !type){
        $('#'+parent_select_id).html('<option value="">Select Parent (optional)</option>');
        return;
    }

    let parent_type = '';
    if(type==='sector') parent_type = '';
    if(type==='road') parent_type = 'road';
    if(type==='block') parent_type = 'block'; // block has no parent

    if(!parent_type){
        $('#'+parent_select_id).html('<option value="">Select Parent (optional)</option>');
        return;
    }

    $.get("{{ url('area-nodes/get') }}/"+area_id+"/"+parent_type,function(data){
        let html = '<option value="">Select Parent (optional)</option>';
        data.forEach(d=>{
            html += `<option value="${d.id}" ${selected_parent_id == d.id ? 'selected' : ''}>${d.name} (${d.type})</option>`;
        });
        $('#'+parent_select_id).html(html);
    });
}

// Add modal: dynamic parent
$('#area,#type').change(function(){
    let area_id = $('#area').val();
    let type = $('#type').val();
    loadParentNodes(area_id,type,'parent');
});

// Edit modal: initialize parent
$('.area-select, .type-select').each(function(){
    let id = $(this).attr('id').split('_').pop();
    let area_id = $('#area_edit_'+id).val();
    let type = $('#type_edit_'+id).val();
    let selected_parent = $('#parent_edit_'+id).data('selected'); // use data-selected instead of val()
    loadParentNodes(area_id,type,'parent_edit_'+id, selected_parent);
});


</script>
@endpush
