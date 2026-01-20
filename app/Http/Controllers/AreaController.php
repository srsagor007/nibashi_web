<?php

namespace App\Http\Controllers;

use App\Imports\Import;
use App\Models\Area;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Str;

class AreaController extends Controller
{
    public function index(){
        $regions = Region::active()->get();
        $areas = Area::query()->with('region')->get();
        return view('area.index', compact('regions', 'areas'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'max:150',
                Rule::unique('areas')->where(function ($query) use ($request) {
                    return $query->where('region_id', $request->region_id);
                }),
            ],
            'region_id' => 'required|exists:regions,id',
            'is_active' => 'required|in:1,0',
        ]);

        try {
            $validated['slug'] = Str::slug($validated['name']);
            Area::create($validated);
            return back()->withSuccess('Area created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }


    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'max:150',
                Rule::unique('areas')->where(function ($query) use ($request, $area) {
                    return $query->where('region_id', $request->region_id)
                                ->where('id', '!=', $area->id);
                }),
            ],
            'region_id' => 'required|exists:regions,id',
        ]);
        
        try {
            $validated['slug'] = Str::slug($validated['name']);
            $area->update($validated);
            return back()->withSuccess('Area updated successfully.');
        } catch (\Exception $e) {
            return back()->withSuccess('error', $e->getMessage());
        }

    }


    public function destroy(Area $area)
    {
        try {
            $area->delete();
            flash()->success('Area deleted successfully');
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function update_status(Request $request, Area $area)
    {
        $request->validate([
            'toggle_input' => 'required|in:true,false',
        ]);

        $area->is_active = $request->toggle_input == 'false' ? false : true;
        $area->save();

        return response()->json($area);
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        try {
            $file = $request->file('file');
            $data = Excel::toArray(new Import(), $file);
            $rows = $data[0] ?? [];
            $rows = array_slice($rows, 1); // skip header

            $success = 0;
            $failed = 0;
            $failedNames = [];

            // Get all valid region IDs (active only)
            $existingRegions = Region::pluck('id')->toArray();

            // Get existing areas (with trashed) grouped by region_id
            $existingAreas = Area::withTrashed()
                ->get()
                ->groupBy('region_id')
                ->map(function ($areas) {
                    return $areas->keyBy(fn($a) => strtolower(trim($a->name)));
                });

            foreach ($rows as $row) {
                $name = trim((string)($row[0] ?? ''));
                $regionId = trim((string)($row[1] ?? ''));

                if ($name === '') {
                    $failed++;
                    $failedNames[] = $name ?: '(empty)';
                    continue;
                }

                // Validate region exists
                if (!$regionId || !is_numeric($regionId) || !in_array($regionId, $existingRegions)) {
                    $failed++;
                    $failedNames[] = $name . ' (invalid region)';
                    continue;
                }

                $normalizedName = strtolower($name);
                $areasInRegion = $existingAreas[$regionId] ?? collect();

                if ($areasInRegion->has($normalizedName)) {
                    $area = $areasInRegion[$normalizedName];

                    if ($area->deleted_at) {
                        // Restore soft deleted area
                        $area->restore();
                        $area->is_active = 1;
                        $area->save();
                        $success++;
                    } else {
                        // Already active → fail
                        $failed++;
                        $failedNames[] = $name . ' (duplicate in region ' . $regionId . ')';
                    }
                    continue;
                }

                // Create new area
                try {
                    $area = Area::create([
                        'name' => $name,
                        'slug' => Str::slug($name),
                        'region_id' => $regionId,
                        'is_active' => 1,
                    ]);
                    $success++;

                    // Add to in-memory list
                    if (!isset($existingAreas[$regionId])) {
                        $existingAreas[$regionId] = collect();
                    }
                    $existingAreas[$regionId]->put($normalizedName, $area);
                } catch (\Throwable) {
                    $failed++;
                    $failedNames[] = $name . ' (creation failed)';
                }
            }

            $msg = "Area bulk upload completed. {$success} success, {$failed} failed";
            if ($failed > 0) {
                $msg .= '. Failed: ' . implode(', ', $failedNames);
            }

            return redirect()->route('areas.index')->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->route('areas.index')->with('error', $e->getMessage());
        }
    }


}
