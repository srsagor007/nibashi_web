<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\District;
use App\Models\Division;

class DistrictController extends Controller
{
    public function index()
    {
        $districts = District::with('division')->latest()->get();
        $divisions = Division::where('is_active', 1)->get();

        return view('districts.index', compact('districts', 'divisions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

         District::create(
            $request->only('division_id', 'name', 'is_active')
        );

        return redirect()->back()->with('success', 'District created successfully.');
    }

    public function update(Request $request, District $district)
    {
        $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'name' => 'required|string|max:255',
        ]);

        $district->update([
            'division_id' => $request->division_id,
            'name' => $request->name,
        ]);

        return redirect()->back()->with('success', 'District updated successfully.');
    }

    public function destroy(District $district)
    {
        $district->delete();
        return redirect()->back()->with('success', 'District deleted successfully.');
    }

    public function updateStatus(District $district, $status)
    {
        $district->update(['is_active' => $status]);
        return redirect()->back()->with('success', 'Status updated.');
    }
}
