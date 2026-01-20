<?php

namespace App\Http\Controllers;

use App\Models\BusinessUnit;
use App\Models\CustomerType;
use Illuminate\Http\Request;
use Str;

class BusinessUnitController extends Controller
{
    public function index(){
        return view('business_unit.index', [
            'business_units' => BusinessUnit::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (BusinessUnit::withTrashed()->where('name', $value)->whereNull('deleted_at')->exists()) {
                        $fail('The Business Unit name has already been taken.');
                    }
                },
            ],
            'is_active' => 'required|boolean',
        ]);
        try{
            $validated['slug'] = Str::slug($validated['name']);
            BusinessUnit::create($validated);
            return back()->withSuccess('Business Unit created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function update(Request $request, BusinessUnit $business_unit)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($business_unit) {
                    if (
                        BusinessUnit::where('name', $value)
                            ->where('id', '!=', $business_unit->id)
                            ->whereNull('deleted_at')
                            ->exists()
                    ) {
                        $fail('The Business Unit name has already been taken.');
                    }
                },
            ],
        ]);
        try {
            $validated['slug'] = Str::slug($validated['name']);
            $business_unit->update($validated);
            return back()->withSuccess('Business Type updated successfully');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function destroy(BusinessUnit $business_unit)
    {
        try {
            $business_unit->delete();
            return back()->withSuccess('Business Unit deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function update_status(Request $request, BusinessUnit $business_unit)
    {
        $request->validate([
            'toggle_input' => 'required|in:true,false',
        ]);

        $business_unit->is_active = $request->toggle_input == 'false' ? false : true;
        $business_unit->save();

        return response()->json($business_unit);
    }
}
