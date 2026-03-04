<?php

namespace App\Http\Controllers\AdminConsole;

use App\Http\Controllers\Controller;
use App\Models\CoinPack;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CoinPackController extends Controller
{
    public function index()
    {
        $coinPacks = CoinPack::query()
            ->orderBy('sort_order')
            ->orderBy('coins')
            ->get();

        return view('admin-console.coin_packs.index', compact('coinPacks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'coins' => ['required', 'integer', 'min:1', 'unique:coin_packs,coins'],
            'price' => ['required', 'numeric', 'min:0'],
            'badge_text' => ['nullable', 'string', 'max:50'],
            'badge_color' => ['nullable', 'string', Rule::in(['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        CoinPack::query()->create([
            'coins' => $validated['coins'],
            'price' => $validated['price'],
            'badge_text' => $validated['badge_text'] ?? null,
            'badge_color' => $validated['badge_color'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'],
        ]);

        return back()->withSuccess('Coin pack created successfully.');
    }

    public function update(Request $request, CoinPack $coin_pack)
    {
        $validated = $request->validate([
            'coins' => ['required', 'integer', 'min:1', Rule::unique('coin_packs', 'coins')->ignore($coin_pack->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'badge_text' => ['nullable', 'string', 'max:50'],
            'badge_color' => ['nullable', 'string', Rule::in(['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $coin_pack->update([
            'coins' => $validated['coins'],
            'price' => $validated['price'],
            'badge_text' => $validated['badge_text'] ?? null,
            'badge_color' => $validated['badge_color'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'],
        ]);

        return back()->withSuccess('Coin pack updated successfully.');
    }

    public function destroy(CoinPack $coin_pack)
    {
        $coin_pack->delete();

        return back()->withSuccess('Coin pack deleted successfully.');
    }

    public function updateStatus(CoinPack $coin_pack, $status)
    {
        $coin_pack->update([
            'is_active' => (int) $status === 1,
        ]);

        return back()->withSuccess('Coin pack status updated successfully.');
    }
}

