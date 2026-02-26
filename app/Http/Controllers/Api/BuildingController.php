<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AreaNode;
use App\Models\Building;
use App\Models\District;
use App\Models\Division;
use App\Models\Thana;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BuildingController extends Controller
{
    public function divisions()
    {
        $data = Division::query()
            ->where('is_active', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->success($data, 'Division list found', 200);
    }

    public function districts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'division_id' => 'required|integer|exists:divisions,id',
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        $data = District::query()
            ->where('is_active', 1)
            ->where('division_id', $request->division_id)
            ->select('id', 'name', 'division_id')
            ->orderBy('name')
            ->get();

        return response()->success($data, 'District list found', 200);
    }

    public function thanas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'district_id' => 'required|integer|exists:districts,id',
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        $data = Thana::query()
            ->where('is_active', 1)
            ->where('district_id', $request->district_id)
            ->select('id', 'name', 'division_id', 'district_id')
            ->orderBy('name')
            ->get();

        return response()->success($data, 'Thana list found', 200);
    }

    public function areas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'thana_id' => 'required|integer|exists:thanas,id',
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        $data = Area::query()
            ->where('is_active', 1)
            ->where('thana_id', $request->thana_id)
            ->select('id', 'name', 'division_id', 'district_id', 'thana_id')
            ->orderBy('name')
            ->get();

        return response()->success($data, 'Area list found', 200);
    }

    public function areaNodes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'area_id' => 'required|integer|exists:areas,id',
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        $data = AreaNode::query()
            ->where('is_active', 1)
            ->where('area_id', $request->area_id)
            ->select('id', 'area_id', 'parent_id', 'type', 'name')
            ->orderByRaw("CASE WHEN type = 'sector' THEN 1 WHEN type = 'block' THEN 2 WHEN type = 'road' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get();

        return response()->success($data, 'Area node list found', 200);
    }

    public function index(Request $request)
    {
        $buildings = Building::query()
            ->where('building_owner', auth()->id())
            ->with([
                'images',
                'division:id,name',
                'district:id,name',
                'thana:id,name',
                'area:id,name',
                'sectorNode:id,area_id,parent_id,type,name',
                'blockNode:id,area_id,parent_id,type,name',
                'roadNode:id,area_id,parent_id,type,name',
            ])
            ->when($request->filled('division_id'), fn ($q) => $q->where('division_id', $request->division_id))
            ->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->district_id))
            ->when($request->filled('thana_id'), fn ($q) => $q->where('thana_id', $request->thana_id))
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->area_id))
            ->latest('id')
            ->paginate($request->integer('per_page', 10));

        return response()->success($buildings, 'Building list found', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required_without:building_name|string|max:255',
            'building_name' => 'required_without:name|string|max:255',
            'address_line' => 'nullable|string|max:500',
            'building_no' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'division_id' => ['required', 'integer', Rule::exists('divisions', 'id')->where('is_active', 1)],
            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where(function ($query) use ($request) {
                    return $query
                        ->where('division_id', $request->division_id)
                        ->where('is_active', 1);
                }),
            ],
            'thana_id' => [
                'required',
                'integer',
                Rule::exists('thanas', 'id')->where(function ($query) use ($request) {
                    return $query
                        ->where('district_id', $request->district_id)
                        ->where('is_active', 1);
                }),
            ],
            'area_id' => [
                'required',
                'integer',
                Rule::exists('areas', 'id')->where(function ($query) use ($request) {
                    return $query
                        ->where('thana_id', $request->thana_id)
                        ->where('is_active', 1);
                }),
            ],
            'sector_node_id' => [
                'nullable',
                'integer',
                Rule::exists('area_nodes', 'id')->where(function ($query) use ($request) {
                    return $query
                        ->where('area_id', $request->area_id)
                        ->where('type', 'sector')
                        ->where('is_active', 1);
                }),
            ],
            'block_node_id' => [
                'nullable',
                'integer',
                Rule::exists('area_nodes', 'id')->where(function ($query) use ($request) {
                    return $query
                        ->where('area_id', $request->area_id)
                        ->where('type', 'block')
                        ->where('is_active', 1);
                }),
            ],
            'road_node_id' => [
                'nullable',
                'integer',
                Rule::exists('area_nodes', 'id')->where(function ($query) use ($request) {
                    return $query
                        ->where('area_id', $request->area_id)
                        ->where('type', 'road')
                        ->where('is_active', 1);
                }),
            ],
            'has_gas' => 'nullable|boolean',
            'has_generator' => 'nullable|boolean',
            'has_lift' => 'nullable|boolean',
            'has_cctv' => 'nullable|boolean',
            'has_security_guard' => 'nullable|boolean',
            'has_parking' => 'nullable|boolean',
            'images' => 'nullable|array|min:0|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        $data = $validator->validated();
        $targetAreaId = (int) $data['area_id'];

        foreach (['sector_node_id' => 'sector', 'block_node_id' => 'block', 'road_node_id' => 'road'] as $field => $type) {
            if (! empty($data[$field])) {
                $validNode = AreaNode::query()
                    ->where('id', $data[$field])
                    ->where('area_id', $targetAreaId)
                    ->where('type', $type)
                    ->where('is_active', 1)
                    ->exists();

                if (! $validNode) {
                    return response()->error("Invalid {$field} for selected area.", null, 422);
                }
            }
        }

        $storedImages = [];

        DB::beginTransaction();

        try {
            $building = Building::query()->create([
                'name' => $data['building_name'] ?? $data['name'],
                'address_line' => $data['address_line'] ?? null,
                'building_owner' => auth()->id(),
                'building_no' => $data['building_no'] ?? null,
                'division_id' => $data['division_id'],
                'district_id' => $data['district_id'],
                'thana_id' => $data['thana_id'],
                'area_id' => $data['area_id'],
                'sector_node_id' => $data['sector_node_id'] ?? null,
                'block_node_id' => $data['block_node_id'] ?? null,
                'road_node_id' => $data['road_node_id'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'has_gas' => (bool) ($data['has_gas'] ?? false),
                'has_generator' => (bool) ($data['has_generator'] ?? false),
                'has_lift' => (bool) ($data['has_lift'] ?? false),
                'has_cctv' => (bool) ($data['has_cctv'] ?? false),
                'has_security_guard' => (bool) ($data['has_security_guard'] ?? false),
                'has_parking' => (bool) ($data['has_parking'] ?? false),
                'created_by' => auth()->id(),
                'is_active' => true,
            ]);

            foreach ($request->file('images', []) as $index => $image) {
                $path = $image->store('buildings', 'public');
                $storedImages[] = $path;

                $building->images()->create([
                    'image_path' => $path,
                    'sort_order' => $index + 1,
                ]);
            }

            DB::commit();

            $building->load([
                'images',
                'division:id,name',
                'district:id,name',
                'thana:id,name',
                'area:id,name',
                'sectorNode:id,area_id,parent_id,type,name',
                'blockNode:id,area_id,parent_id,type,name',
                'roadNode:id,area_id,parent_id,type,name',
            ]);

            return response()->success($building, 'Building created successfully', 201);
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
        $building = Building::query()
            ->with([
                'images',
                'division:id,name',
                'district:id,name',
                'thana:id,name',
                'area:id,name',
                'sectorNode:id,area_id,parent_id,type,name',
                'blockNode:id,area_id,parent_id,type,name',
                'roadNode:id,area_id,parent_id,type,name',
                'flats.images',
            ])
            ->find($id);

        if (! $building) {
            return response()->error('Building not found', null, 404);
        }

        return response()->success($building, 'Building details found', 200);
    }

    public function update(Request $request, $id)
    {
        $building = Building::query()->with('images')->find($id);

        if (! $building) {
            return response()->error('Building not found', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required_without:building_name|string|max:255',
            'building_name' => 'sometimes|required_without:name|string|max:255',
            'address_line' => 'nullable|string|max:500',
            'building_no' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'division_id' => ['sometimes', 'integer', Rule::exists('divisions', 'id')->where('is_active', 1)],
            'district_id' => ['sometimes', 'integer', Rule::exists('districts', 'id')->where('is_active', 1)],
            'thana_id' => ['sometimes', 'integer', Rule::exists('thanas', 'id')->where('is_active', 1)],
            'area_id' => ['sometimes', 'integer', Rule::exists('areas', 'id')->where('is_active', 1)],
            'sector_node_id' => ['sometimes', 'nullable', 'integer', Rule::exists('area_nodes', 'id')->where('type', 'sector')->where('is_active', 1)],
            'block_node_id' => ['sometimes', 'nullable', 'integer', Rule::exists('area_nodes', 'id')->where('type', 'block')->where('is_active', 1)],
            'road_node_id' => ['sometimes', 'nullable', 'integer', Rule::exists('area_nodes', 'id')->where('type', 'road')->where('is_active', 1)],
            'has_gas' => 'nullable|boolean',
            'has_generator' => 'nullable|boolean',
            'has_lift' => 'nullable|boolean',
            'has_cctv' => 'nullable|boolean',
            'has_security_guard' => 'nullable|boolean',
            'has_parking' => 'nullable|boolean',
            'replace_images' => 'nullable|boolean',
            'images' => 'sometimes|array|min:0|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        $data = $validator->validated();
        $targetAreaId = (int) ($data['area_id'] ?? $building->area_id);

        foreach (['sector_node_id' => 'sector', 'block_node_id' => 'block', 'road_node_id' => 'road'] as $field => $type) {
            if (! empty($data[$field])) {
                $validNode = AreaNode::query()
                    ->where('id', $data[$field])
                    ->where('area_id', $targetAreaId)
                    ->where('type', $type)
                    ->where('is_active', 1)
                    ->exists();

                if (! $validNode) {
                    return response()->error("Invalid {$field} for selected area.", null, 422);
                }
            }
        }

        $storedImages = [];
        $oldImages = [];

        DB::beginTransaction();

        try {
            $building->update([
                'name' => $data['building_name'] ?? $data['name'] ?? $building->name,
                'address_line' => $data['address_line'] ?? $building->address_line,
                'building_owner' => $building->building_owner ?? $building->created_by,
                'building_no' => $data['building_no'] ?? $building->building_no,
                'division_id' => $data['division_id'] ?? $building->division_id,
                'district_id' => $data['district_id'] ?? $building->district_id,
                'thana_id' => $data['thana_id'] ?? $building->thana_id,
                'area_id' => $data['area_id'] ?? $building->area_id,
                'sector_node_id' => array_key_exists('sector_node_id', $data) ? $data['sector_node_id'] : $building->sector_node_id,
                'block_node_id' => array_key_exists('block_node_id', $data) ? $data['block_node_id'] : $building->block_node_id,
                'road_node_id' => array_key_exists('road_node_id', $data) ? $data['road_node_id'] : $building->road_node_id,
                'latitude' => $data['latitude'] ?? $building->latitude,
                'longitude' => $data['longitude'] ?? $building->longitude,
                'has_gas' => $data['has_gas'] ?? $building->has_gas,
                'has_generator' => $data['has_generator'] ?? $building->has_generator,
                'has_lift' => $data['has_lift'] ?? $building->has_lift,
                'has_cctv' => $data['has_cctv'] ?? $building->has_cctv,
                'has_security_guard' => $data['has_security_guard'] ?? $building->has_security_guard,
                'has_parking' => $data['has_parking'] ?? $building->has_parking,
            ]);

            if (! empty($data['images'])) {
                $replace = (bool) ($data['replace_images'] ?? false);

                if ($replace) {
                    $oldImages = $building->images->pluck('image_path')->all();
                    $building->images()->delete();
                }

                $sortOrder = (int) $building->images()->max('sort_order');
                foreach ($request->file('images', []) as $image) {
                    $path = $image->store('buildings', 'public');
                    $storedImages[] = $path;

                    $building->images()->create([
                        'image_path' => $path,
                        'sort_order' => ++$sortOrder,
                    ]);
                }
            }

            DB::commit();

            foreach ($oldImages as $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }

            $building->load([
                'images',
                'division:id,name',
                'district:id,name',
                'thana:id,name',
                'area:id,name',
                'sectorNode:id,area_id,parent_id,type,name',
                'blockNode:id,area_id,parent_id,type,name',
                'roadNode:id,area_id,parent_id,type,name',
            ]);

            return response()->success($building, 'Building updated successfully', 200);
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
        $building = Building::query()->with('images')->find($id);

        if (! $building) {
            return response()->error('Building not found', null, 404);
        }

        DB::beginTransaction();

        try {
            $imagePaths = $building->images->pluck('image_path')->all();

            $building->images()->delete();
            $building->delete();

            DB::commit();

            foreach ($imagePaths as $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->success(null, 'Building deleted successfully', 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->error($th->getMessage(), null, 500);
        }
    }
}

