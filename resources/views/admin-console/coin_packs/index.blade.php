@extends('layouts.base')
@section('title', 'Admin Console | Coin Packs')

@php
    $breadcrumb = [
        ['title' => 'Admin Console'],
        ['title' => 'Coin Packs', 'url' => route('coin-packs.index')],
    ];

    $badgeColors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
@endphp

@section('content')
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <x-alert />

            <button type="button" class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#add_coin_pack_modal">
                New Coin Pack
            </button>

            <div class="card card-flush h-xl-100">
                <div class="card-body">
                    <x-table id="coin-pack-table">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th>Coins</th>
                                <th>Price (Tk)</th>
                                <th>Badge</th>
                                <th>Sort</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($coinPacks as $coinPack)
                                <tr>
                                    <td>{{ number_format($coinPack->coins) }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float) $coinPack->price, 2, '.', ''), '0'), '.') }}</td>
                                    <td>
                                        @if ($coinPack->badge_text)
                                            <span class="badge badge-light-{{ $coinPack->badge_color ?: 'primary' }}">
                                                {{ $coinPack->badge_text }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $coinPack->sort_order }}</td>
                                    <td>
                                        {!! $coinPack->toggleButton(
                                            route('coin-packs.update-status', [
                                                'coin_pack' => $coinPack->id,
                                                'status' => $coinPack->is_active ? 0 : 1,
                                            ])
                                        ) !!}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-light-primary btn-icon btn-sm me-2" data-bs-toggle="modal"
                                            data-bs-target="#update_coin_pack_modal_{{ $coinPack->id }}">
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <button type="button" class="btn btn-sm btn-light-danger btn-icon"
                                            onclick="confirmDelete('coinPackDelete{{ $coinPack->id }}')">
                                            <i class="fa fa-trash"></i>
                                        </button>

                                        <form method="POST" action="{{ route('coin-packs.destroy', ['coin_pack' => $coinPack->id]) }}"
                                            id="coinPackDelete{{ $coinPack->id }}">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>

                                <x-modal id="update_coin_pack_modal_{{ $coinPack->id }}" title="Update Coin Pack">
                                    <form action="{{ route('coin-packs.update', ['coin_pack' => $coinPack->id]) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body">
                                            <x-form-group>
                                                <x-form-label for="coins_{{ $coinPack->id }}" required="true">Coins</x-form-label>
                                                <input type="number" min="1" name="coins" id="coins_{{ $coinPack->id }}"
                                                    class="form-control" value="{{ $coinPack->coins }}" required />
                                            </x-form-group>

                                            <x-form-group>
                                                <x-form-label for="price_{{ $coinPack->id }}" required="true">Price (Tk)</x-form-label>
                                                <input type="number" step="0.01" min="0" name="price" id="price_{{ $coinPack->id }}"
                                                    class="form-control" value="{{ number_format((float) $coinPack->price, 2, '.', '') }}" required />
                                            </x-form-group>

                                            <x-form-group>
                                                <x-form-label for="badge_text_{{ $coinPack->id }}">Badge Text</x-form-label>
                                                <input type="text" name="badge_text" id="badge_text_{{ $coinPack->id }}"
                                                    class="form-control" value="{{ $coinPack->badge_text }}" placeholder="Popular / Value Pack" />
                                            </x-form-group>

                                            <x-form-group>
                                                <x-form-label for="badge_color_{{ $coinPack->id }}">Badge Color</x-form-label>
                                                <select name="badge_color" id="badge_color_{{ $coinPack->id }}" class="form-select">
                                                    <option value="">None</option>
                                                    @foreach ($badgeColors as $badgeColor)
                                                        <option value="{{ $badgeColor }}" @selected($coinPack->badge_color === $badgeColor)>
                                                            {{ ucfirst($badgeColor) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </x-form-group>

                                            <x-form-group>
                                                <x-form-label for="sort_order_{{ $coinPack->id }}">Sort Order</x-form-label>
                                                <input type="number" min="0" name="sort_order" id="sort_order_{{ $coinPack->id }}"
                                                    class="form-control" value="{{ $coinPack->sort_order }}" />
                                            </x-form-group>

                                            <x-form-group>
                                                <x-form-label for="is_active_{{ $coinPack->id }}" required="true">Status</x-form-label>
                                                <select name="is_active" id="is_active_{{ $coinPack->id }}" class="form-select" required>
                                                    <option value="1" @selected((int) $coinPack->is_active === 1)>Active</option>
                                                    <option value="0" @selected((int) $coinPack->is_active === 0)>Inactive</option>
                                                </select>
                                            </x-form-group>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Update</button>
                                        </div>
                                    </form>
                                </x-modal>
                            @endforeach
                        </tbody>
                    </x-table>
                </div>
            </div>
        </div>
    </div>

    <x-modal id="add_coin_pack_modal" title="Add Coin Pack">
        <form action="{{ route('coin-packs.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <x-form-group>
                    <x-form-label for="coins" required="true">Coins</x-form-label>
                    <input type="number" min="1" name="coins" id="coins" class="form-control" value="{{ old('coins') }}"
                        required />
                </x-form-group>

                <x-form-group>
                    <x-form-label for="price" required="true">Price (Tk)</x-form-label>
                    <input type="number" step="0.01" min="0" name="price" id="price" class="form-control"
                        value="{{ old('price') }}" required />
                </x-form-group>

                <x-form-group>
                    <x-form-label for="badge_text">Badge Text</x-form-label>
                    <input type="text" name="badge_text" id="badge_text" class="form-control" value="{{ old('badge_text') }}"
                        placeholder="Popular / Value Pack" />
                </x-form-group>

                <x-form-group>
                    <x-form-label for="badge_color">Badge Color</x-form-label>
                    <select name="badge_color" id="badge_color" class="form-select">
                        <option value="">None</option>
                        @foreach ($badgeColors as $badgeColor)
                            <option value="{{ $badgeColor }}" @selected(old('badge_color') === $badgeColor)>{{ ucfirst($badgeColor) }}</option>
                        @endforeach
                    </select>
                </x-form-group>

                <x-form-group>
                    <x-form-label for="sort_order">Sort Order</x-form-label>
                    <input type="number" min="0" name="sort_order" id="sort_order" class="form-control"
                        value="{{ old('sort_order', 0) }}" />
                </x-form-group>

                <x-form-group>
                    <x-form-label for="is_active" required="true">Status</x-form-label>
                    <select name="is_active" id="is_active" class="form-select" required>
                        <option value="1" @selected(old('is_active', '1') === '1')>Active</option>
                        <option value="0" @selected(old('is_active') === '0')>Inactive</option>
                    </select>
                </x-form-group>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </x-modal>
@endsection

