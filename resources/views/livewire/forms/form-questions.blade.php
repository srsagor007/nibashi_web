<div>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <button type="button" class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal"
            data-bs-target="#add_question_modal">
            New Question
        </button>
        <div class="card card-flush">
            <div class="card-body">
                {{ $form }}

                {{-- {{ $field_types }} --}}
            </div>
        </div>
    </div>
</div>



<x-modal id="add_question_modal" title="Add New Question">
    <form action="{{ route('forms.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
            <x-form-group>
                <x-form-label required="true" for="title">Title</x-form-label>
                <input type="text" name="title" id="title" class="form-control" placeholder="Form title"
                    value="{{ old('title') }}" required />
            </x-form-group>

            <x-form-group>
                <x-form-label for="question_set_id">Question Set</x-form-label>
                <select name="question_set_id" id="question_set_id" class="form-select">
                    <option value="">Default</option>
                </select>
            </x-form-group>

            <x-form-group>
                <x-form-label for="time_limit_in_minutes">Time limit in minutes (<small>if
                        applicable</small>)</x-form-label>
                <input type="number" name="time_limit_in_minutes" id="time_limit_in_minutes" class="form-control"
                    value="{{ old('time_limit_in_minutes') }}" placeholder="Time limit" />
            </x-form-group>

            <x-form-group>
                <x-form-label for="description">Description</x-form-label>
                <textarea name="description" id="description" cols="30" rows="3" class="form-control">{{ old('description') }}</textarea>
            </x-form-group>

            <x-form-group>
                <x-form-label for="is_active" required="true">Status</x-form-label>
                <select name="is_active" id="is_active" class="form-select">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </x-form-group>

            <x-form-group>
                <x-form-label for="is_published" required="true">Is Published</x-form-label>
                <select name="is_published" id="is_published" class="form-select">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </x-form-group>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</x-modal>

</div>
