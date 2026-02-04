<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\District;
use App\Models\Division;
use App\Models\Thana;

class ThanaController extends Controller
{
     public function index()
    {
        $thanas = Thana::with(['division','district'])->latest()->get();
        $divisions = Division::where('is_active',1)->get();
        $districts = District::where('is_active',1)->get();


        return view('thanas.index', compact('thanas','divisions','districts'));
    }

    public function getDistricts($division_id)
    {
        return District::where('division_id',$division_id)
                        ->where('is_active',1)
                        ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'district_id' => 'required|exists:districts,id',
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        Thana::create(
            $request->only('division_id','district_id','name','is_active')
        );

        return back()->with('success','Thana created successfully');
    }

    public function update(Request $request, Thana $thana)
    {
        $request->validate([
            'division_id' => 'required',
            'district_id' => 'required',
            'name' => 'required'
        ]);

        $thana->update(
            $request->only('division_id','district_id','name')
        );

        return back()->with('success','Thana updated successfully');
    }

    public function destroy(Thana $thana)
    {
        $thana->delete();
        return back()->with('success','Thana deleted');
    }

    public function updateStatus(Thana $thana, $status)
    {
        $thana->update(['is_active'=>$status]);
        return back()->with('success','Status updated');
    }
}
