<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Flat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FlatController extends Controller
{
    private function normalizeStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $status = strtolower(trim($status));

        return match ($status) {
            'vacant', 'vecent' => 'vacant',
            'rent', 'rented' => 'rent',
            default => $status,
        };
    }

    public function indexByBuilding($buildingId)
    {
        $building = $this->ownedBuilding($buildingId);

        if (! $building) {
            return response()->error('Building not found', null, 404);
        }

        $flats = Flat::query()
            ->with('images')
            ->where('building_id', $building->id)
            ->latest('id')
            ->get();

        return response()->success($flats, 'Flat list found', 200);
    }

    public function store(Request $request, $buildingId)
    {
        $building = $this->ownedBuilding($buildingId);

        if (! $building) {
            return response()->error('Building not found', null, 404);
        }

        $request->merge([
            'status' => $this->normalizeStatus($request->input('status')),
        ]);

        $validator = Validator::make($request->all(), [
            'flat_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('flats', 'flat_number')->where(function ($q) use ($building) {
                    return $q->where('building_id', $building->id);
                }),
            ],
            'floor_no' => 'required|integer|min:0|max:200',
            'bed_room' => 'required|integer|min:0|max:20',
            'bathroom' => 'required|integer|min:0|max:20',
            'balcony' => 'required|integer|min:0|max:20',
            'kitchen' => 'required|integer|min:0|max:20',
            'dining' => 'required|integer|min:0|max:20',
            'drawing' => 'required|integer|min:0|max:20',
            'house_rent' => 'required|numeric|min:0|max:99999999.99',
            'service_charge' => 'nullable|numeric|min:0|max:99999999.99',
            'is_furnished' => 'nullable|boolean',
            'preferable' => ['required', Rule::in(['family', 'bachelor', 'office'])],
            'status' => ['required', Rule::in(['vacant', 'rent'])],
            'vacant_date' => 'nullable|date',
            'total_flat_size' => 'required|numeric|min:0|max:999999.99',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        $data = $validator->validated();
        $storedImages = [];

        DB::beginTransaction();

        try {
            $flat = Flat::query()->create([
                'building_id' => $building->id,
                'flat_number' => $data['flat_number'],
                'floor_no' => $data['floor_no'],
                'bed_room' => $data['bed_room'],
                'bathroom' => $data['bathroom'],
                'balcony' => $data['balcony'],
                'kitchen' => $data['kitchen'],
                'dining' => $data['dining'],
                'drawing' => $data['drawing'],
                'house_rent' => $data['house_rent'],
                'service_charge' => $data['service_charge'] ?? 0,
                'is_furnished' => (bool) ($data['is_furnished'] ?? false),
                'preferable' => $data['preferable'],
                'status' => $data['status'],
                'vacant_date' => $data['status'] === 'vacant' ? ($data['vacant_date'] ?? now()->toDateString()) : null,
                'total_flat_size' => $data['total_flat_size'],
                'created_by' => auth()->id(),
                'is_active' => true,
            ]);

            foreach ($request->file('images', []) as $index => $image) {
                $path = $image->store('flats', 'public');
                $storedImages[] = $path;

                $flat->images()->create([
                    'image_path' => $path,
                    'sort_order' => $index + 1,
                ]);
            }

            DB::commit();

            return response()->success($flat->load('images'), 'Flat created successfully', 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            foreach ($storedImages as $storedImage) {
                Storage::disk('public')->delete($storedImage);
            }

            return response()->error($th->getMessage(), null, 500);
        }
    }

    public function show($id)
    {
        $flat = Flat::query()
            ->with(['building:id,name,building_owner', 'images'])
            ->find($id);

        if (! $flat || (int) $flat->building?->building_owner !== (int) auth()->id()) {
            return response()->error('Flat not found', null, 404);
        }

        return response()->success($flat, 'Flat details found', 200);
    }

    public function update(Request $request, $id)
    {
        $flat = Flat::query()->with(['building:id,building_owner', 'images'])->find($id);

        if (! $flat || (int) $flat->building?->building_owner !== (int) auth()->id()) {
            return response()->error('Flat not found', null, 404);
        }

        $request->merge([
            'status' => $this->normalizeStatus($request->input('status')),
        ]);

        $validator = Validator::make($request->all(), [
            'flat_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('flats', 'flat_number')
                    ->where(function ($q) use ($flat) {
                        return $q->where('building_id', $flat->building_id);
                    })
                    ->ignore($flat->id),
            ],
            'floor_no' => 'sometimes|required|integer|min:0|max:200',
            'bed_room' => 'sometimes|required|integer|min:0|max:20',
            'bathroom' => 'sometimes|required|integer|min:0|max:20',
            'balcony' => 'sometimes|required|integer|min:0|max:20',
            'kitchen' => 'sometimes|required|integer|min:0|max:20',
            'dining' => 'sometimes|required|integer|min:0|max:20',
            'drawing' => 'sometimes|required|integer|min:0|max:20',
            'house_rent' => 'sometimes|required|numeric|min:0|max:99999999.99',
            'service_charge' => 'sometimes|nullable|numeric|min:0|max:99999999.99',
            'is_furnished' => 'sometimes|nullable|boolean',
            'preferable' => ['sometimes', 'required', Rule::in(['family', 'bachelor', 'office'])],
            'status' => ['sometimes', 'required', Rule::in(['vacant', 'rent'])],
            'vacant_date' => 'sometimes|nullable|date',
            'total_flat_size' => 'sometimes|required|numeric|min:0|max:999999.99',
            'images' => 'sometimes|nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:10240',
            'replace_images' => 'sometimes|boolean',
            'remove_image_ids' => 'sometimes|array',
            'remove_image_ids.*' => 'integer|exists:flat_images,id',
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        $data = $validator->validated();
        $storedImages = [];
        $deletedPaths = [];

        DB::beginTransaction();

        try {
            $flat->update([
                'flat_number' => $data['flat_number'] ?? $flat->flat_number,
                'floor_no' => $data['floor_no'] ?? $flat->floor_no,
                'bed_room' => $data['bed_room'] ?? $flat->bed_room,
                'bathroom' => $data['bathroom'] ?? $flat->bathroom,
                'balcony' => $data['balcony'] ?? $flat->balcony,
                'kitchen' => $data['kitchen'] ?? $flat->kitchen,
                'dining' => $data['dining'] ?? $flat->dining,
                'drawing' => $data['drawing'] ?? $flat->drawing,
                'house_rent' => $data['house_rent'] ?? $flat->house_rent,
                'service_charge' => array_key_exists('service_charge', $data) ? ($data['service_charge'] ?? 0) : $flat->service_charge,
                'is_furnished' => array_key_exists('is_furnished', $data) ? (bool) $data['is_furnished'] : $flat->is_furnished,
                'preferable' => $data['preferable'] ?? $flat->preferable,
                'status' => $data['status'] ?? $flat->status,
                'vacant_date' => array_key_exists('status', $data)
                    ? (($data['status'] === 'vacant') ? ($data['vacant_date'] ?? $flat->vacant_date ?? now()->toDateString()) : null)
                    : (array_key_exists('vacant_date', $data) ? $data['vacant_date'] : $flat->vacant_date),
                'total_flat_size' => $data['total_flat_size'] ?? $flat->total_flat_size,
            ]);

            if (($data['replace_images'] ?? false) === true) {
                $deletedPaths = array_merge($deletedPaths, $flat->images->pluck('image_path')->all());
                $flat->images()->delete();
                $flat->unsetRelation('images');
                $flat->load('images');
            }

            if (! empty($data['remove_image_ids'])) {
                $toDelete = $flat->images()->whereIn('id', $data['remove_image_ids'])->get();
                $deletedPaths = array_merge($deletedPaths, $toDelete->pluck('image_path')->all());
                $flat->images()->whereIn('id', $data['remove_image_ids'])->delete();
            }

            if (! empty($data['images'])) {
                $sortOrder = (int) $flat->images()->max('sort_order');
                foreach ($request->file('images', []) as $image) {
                    $path = $image->store('flats', 'public');
                    $storedImages[] = $path;

                    $flat->images()->create([
                        'image_path' => $path,
                        'sort_order' => ++$sortOrder,
                    ]);
                }
            }

            DB::commit();

            foreach ($deletedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            return response()->success($flat->fresh()->load('images'), 'Flat updated successfully', 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            foreach ($storedImages as $storedImage) {
                Storage::disk('public')->delete($storedImage);
            }

            return response()->error($th->getMessage(), null, 500);
        }
    }

    public function destroy($id)
    {
        $flat = Flat::query()->with(['building:id,building_owner', 'images'])->find($id);

        if (! $flat || (int) $flat->building?->building_owner !== (int) auth()->id()) {
            return response()->error('Flat not found', null, 404);
        }

        $imagePaths = $flat->images->pluck('image_path')->all();
        $flat->delete();

        foreach ($imagePaths as $path) {
            Storage::disk('public')->delete($path);
        }

        return response()->success(null, 'Flat deleted successfully', 200);
    }

    private function ownedBuilding($buildingId): ?Building
    {
        return Building::query()
            ->where('id', $buildingId)
            ->where('building_owner', auth()->id())
            ->first();
    }
}
