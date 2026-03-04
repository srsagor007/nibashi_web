<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Flat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function availableList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'division_id' => 'nullable|integer|exists:divisions,id',
            'district_id' => 'nullable|integer|exists:districts,id',
            'thana_id' => 'nullable|integer|exists:thanas,id',
            'area_id' => 'nullable|integer|exists:areas,id',
            'min_rent' => 'nullable|numeric|min:0|max:99999999.99',
            'max_rent' => 'nullable|numeric|min:0|max:99999999.99|gte:min_rent',
            'bed_room' => 'nullable|integer|min:0|max:20',
            'bathroom' => 'nullable|integer|min:0|max:20',
            'is_furnished' => 'nullable|boolean',
            'search' => 'nullable|string|max:100',
            'sort_by' => ['nullable', Rule::in(['latest', 'rent_asc', 'rent_desc'])],
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        $flats = $this->availableFlatsQuery($request)
            ->with([
                'images',
                'building:id,name,address_line,division_id,district_id,thana_id,area_id,has_gas,has_generator,has_lift,has_cctv,has_security_guard,has_parking',
                'building.division:id,name',
                'building.district:id,name',
                'building.thana:id,name',
                'building.area:id,name',
            ]);

        $sortBy = $request->input('sort_by', 'latest');
        if ($sortBy === 'rent_asc') {
            $flats->orderBy('house_rent', 'asc')->orderBy('id', 'desc');
        } elseif ($sortBy === 'rent_desc') {
            $flats->orderBy('house_rent', 'desc')->orderBy('id', 'desc');
        } else {
            $flats->latest('id');
        }

        $paginator = $flats->paginate($request->integer('per_page', 10));

        return response()->success($this->mapAvailableListPaginated($paginator), 'Available flat list found', 200);
    }

    public function availableDetails($id)
    {
        $flat = $this->availableFlatsQuery()
            ->with([
                'images',
                'building:id,name,address_line,building_no,latitude,longitude,division_id,district_id,thana_id,area_id,has_gas,has_generator,has_lift,has_cctv,has_security_guard,has_parking',
                'building.division:id,name',
                'building.district:id,name',
                'building.thana:id,name',
                'building.area:id,name',
                'building.sectorNode:id,name',
                'building.blockNode:id,name',
                'building.roadNode:id,name',
            ])
            ->whereKey($id)
            ->first();

        if (! $flat instanceof Flat) {
            return response()->error('Flat not found', null, 404);
        }

        return response()->success($this->mapAvailableDetails($flat), 'Available flat details found', 200);
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
        $buildingOwner = (int) data_get($flat, 'building.building_owner', 0);

        if (! $flat || $buildingOwner !== (int) auth()->id()) {
            return response()->error('Flat not found', null, 404);
        }

        return response()->success($flat, 'Flat details found', 200);
    }

    public function update(Request $request, $id)
    {
        $flat = Flat::query()->with(['building:id,building_owner', 'images'])->find($id);
        $buildingOwner = (int) data_get($flat, 'building.building_owner', 0);

        if (! $flat || $buildingOwner !== (int) auth()->id()) {
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
        $buildingOwner = (int) data_get($flat, 'building.building_owner', 0);

        if (! $flat || $buildingOwner !== (int) auth()->id()) {
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

    private function availableFlatsQuery(?Request $request = null): Builder
    {
        return Flat::query()
            ->where('flats.status', 'vacant')
            ->where('flats.is_active', true)
            ->whereHas('building', function (Builder $query) use ($request) {
                $query->where('is_active', true)
                    ->when($request?->has('division_id'), fn (Builder $q) => $q->where('division_id', $request->division_id))
                    ->when($request?->has('district_id'), fn (Builder $q) => $q->where('district_id', $request->district_id))
                    ->when($request?->has('thana_id'), fn (Builder $q) => $q->where('thana_id', $request->thana_id))
                    ->when($request?->has('area_id'), fn (Builder $q) => $q->where('area_id', $request->area_id));
            })
            ->when($request?->has('min_rent'), fn (Builder $query) => $query->where('house_rent', '>=', $request->min_rent))
            ->when($request?->has('max_rent'), fn (Builder $query) => $query->where('house_rent', '<=', $request->max_rent))
            ->when($request?->has('bed_room'), fn (Builder $query) => $query->where('bed_room', $request->bed_room))
            ->when($request?->has('bathroom'), fn (Builder $query) => $query->where('bathroom', $request->bathroom))
            ->when($request?->has('is_furnished'), fn (Builder $query) => $query->where('is_furnished', (bool) $request->boolean('is_furnished')))
            ->when($request?->filled('search'), function (Builder $query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function (Builder $q) use ($search) {
                    $q->where('flat_number', 'like', "%{$search}%")
                        ->orWhereHas('building', function (Builder $b) use ($search) {
                            $b->where('name', 'like', "%{$search}%")
                                ->orWhere('address_line', 'like', "%{$search}%");
                        });
                });
            });
    }

    private function mapAvailableListPaginated(LengthAwarePaginator $paginator): array
    {
        /** @var array<int, Flat> $items */
        $items = $paginator->items();
        $data = [];

        foreach ($items as $flat) {
            /** @var Building|null $building */
            $building = $flat->building;

            $data[] = [
                'id' => $flat->id,
                'flat_number' => $flat->flat_number,
                'status' => $flat->status,
                'vacant_date' => $flat->vacant_date?->format('Y-m-d'),
                'house_rent' => $flat->house_rent,
                'service_charge' => $flat->service_charge,
                'total_flat_size' => $flat->total_flat_size,
                'floor_no' => $flat->floor_no,
                'bed_room' => $flat->bed_room,
                'bathroom' => $flat->bathroom,
                'balcony' => $flat->balcony,
                'is_furnished' => $flat->is_furnished,
                'image_url' => $flat->image_url,
                'location' => [
                    'division' => data_get($building, 'division.name'),
                    'district' => data_get($building, 'district.name'),
                    'thana' => data_get($building, 'thana.name'),
                    'area' => data_get($building, 'area.name'),
                    'address_line' => $building?->address_line,
                    'text' => implode(', ', array_values(array_filter([
                        data_get($building, 'area.name'),
                        data_get($building, 'thana.name') ?? data_get($building, 'district.name'),
                    ]))),
                ],
                'building' => [
                    'id' => $building?->id,
                    'name' => $building?->name,
                    'has_gas' => (bool) $building?->has_gas,
                    'has_generator' => (bool) $building?->has_generator,
                    'has_lift' => (bool) $building?->has_lift,
                    'has_cctv' => (bool) $building?->has_cctv,
                    'has_security_guard' => (bool) $building?->has_security_guard,
                    'has_parking' => (bool) $building?->has_parking,
                ],
            ];
        }

        return [
            'current_page' => $paginator->currentPage(),
            'data' => $data,
            'first_page_url' => $paginator->url(1),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'last_page_url' => $paginator->url($paginator->lastPage()),
            'next_page_url' => $paginator->nextPageUrl(),
            'path' => $paginator->path(),
            'per_page' => $paginator->perPage(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];
    }

    private function mapAvailableDetails(Flat $flat): array
    {
        /** @var Building|null $building */
        $building = $flat->building;

        return [
            'id' => $flat->id,
            'flat_number' => $flat->flat_number,
            'status' => $flat->status,
            'vacant_date' => $flat->vacant_date?->format('Y-m-d'),
            'house_rent' => $flat->house_rent,
            'service_charge' => $flat->service_charge,
            'total_flat_size' => $flat->total_flat_size,
            'floor_no' => $flat->floor_no,
            'bed_room' => $flat->bed_room,
            'bathroom' => $flat->bathroom,
            'balcony' => $flat->balcony,
            'kitchen' => $flat->kitchen,
            'dining' => $flat->dining,
            'drawing' => $flat->drawing,
            'is_furnished' => $flat->is_furnished,
            'preferable' => $flat->preferable,
            'images' => $flat->images->map(static fn ($image): array => [
                'id' => (int) data_get($image, 'id'),
                'sort_order' => (int) data_get($image, 'sort_order'),
                'image_url' => (string) data_get($image, 'image_url'),
            ])->values()->all(),
            'building' => [
                'id' => $building?->id,
                'name' => $building?->name,
                'building_no' => $building?->building_no,
                'address_line' => $building?->address_line,
                'latitude' => $building?->latitude,
                'longitude' => $building?->longitude,
                'division' => data_get($building, 'division.name'),
                'district' => data_get($building, 'district.name'),
                'thana' => data_get($building, 'thana.name'),
                'area' => data_get($building, 'area.name'),
                'sector' => data_get($building, 'sectorNode.name'),
                'block' => data_get($building, 'blockNode.name'),
                'road' => data_get($building, 'roadNode.name'),
                'has_gas' => (bool) $building?->has_gas,
                'has_generator' => (bool) $building?->has_generator,
                'has_lift' => (bool) $building?->has_lift,
                'has_cctv' => (bool) $building?->has_cctv,
                'has_security_guard' => (bool) $building?->has_security_guard,
                'has_parking' => (bool) $building?->has_parking,
            ],
        ];
    }
}
