<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Division;

class DivisionController extends Controller
{
    public function index()
    {
        $divisions = Division::latest()->get();
        return view('divisions.index', compact('divisions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:divisions,name',
            'is_active' => 'required|boolean',
        ]);

        Division::create([
            'name' => $request->name,
            'is_active' => $request->is_active,
        ]);

        return redirect()->back()->with('success', 'Division created successfully.');
    }

    public function update(Request $request, Division $division)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:divisions,name,' . $division->id,
        ]);

        $division->update([
            'name' => $request->name,
        ]);

        return redirect()->back()->with('success', 'Division updated successfully.');
    }

    public function destroy(Division $division)
    {
        $division->delete();
        return redirect()->back()->with('success', 'Division deleted successfully.');
    }

    public function updateStatus(Division $division, $status)
    {
        $division->update([
            'is_active' => $status
        ]);

        return redirect()->back()->with('success', 'Status updated.');
    }
}
