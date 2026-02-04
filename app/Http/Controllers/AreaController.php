<?php

namespace App\Http\Controllers;

use App\Imports\Import;
use App\Models\Area;
use Illuminate\Http\Request;
use App\Models\District;
use App\Models\Division;
use App\Models\Thana;

class AreaController extends Controller
{
   
 public function index()
    {
        $areas = Area::with(['division','district','thana'])->latest()->get();
        $divisions = Division::where('is_active',1)->get();

        return view('areas.index', compact('areas','divisions'));
    }

    public function getDistricts($division_id)
    {
        return District::where('division_id',$division_id)
                        ->where('is_active',1)->get();
    }

    public function getThanas($district_id)
    {
        return Thana::where('district_id',$district_id)
                     ->where('is_active',1)->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'division_id' => 'required',
            'district_id' => 'required',
            'thana_id' => 'required',
            'name' => 'required',
            'is_active' => 'required|boolean'
        ]);

        Area::create(
            $request->only(
                'division_id','district_id','thana_id','name','is_active'
            )
        );

        return back()->with('success','Area created successfully');
    }

    public function update(Request $request, Area $area)
    {
        $area->update(
            $request->only(
                'division_id','district_id','thana_id','name'
            )
        );

        return back()->with('success','Area updated successfully');
    }

    public function destroy(Area $area)
    {
        $area->delete();
        return back()->with('success','Area deleted');
    }

    public function updateStatus(Area $area, $status)
    {
        $area->update(['is_active'=>$status]);
        return back()->with('success','Status updated');
    }


}
