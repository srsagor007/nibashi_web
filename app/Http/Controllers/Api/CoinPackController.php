<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoinPack;

class CoinPackController extends Controller
{
    public function list()
    {
        $coinPacks = CoinPack::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('coins')
            ->get([
                'id',
                'coins',
                'price',
                'badge_text',
                'badge_color',
                'sort_order',
            ]);

        return response()->success($coinPacks, 'Coin pack list found', 200);
    }
}

