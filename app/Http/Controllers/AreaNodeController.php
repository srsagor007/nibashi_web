<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AreaNode;
use Illuminate\Http\Request;

class AreaNodeController extends Controller
{
    public function index()
    {
        $areas = Area::all();
        $nodes = AreaNode::with(['area','parent.parent.parent'])->latest()->get();

        return view('area_nodes.index', compact('areas','nodes'));
    }

    // Get parent options dynamically
    public function getNodes($area_id, $type)
    {
        $query = AreaNode::where('area_id',$area_id)->where('is_active',1);

        // Determine allowed parent types
        if($type==='block'){
            $query->where('type','sector'); // sector can only have block parent
        }
        elseif($type==='road'){
            $query->whereIn('type',['block','sector']); // road can have block or sector parent
        }
        else{
            // block or other types have no parent
            return [];
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'parent_id' => 'nullable|exists:area_nodes,id',
            'type' => 'required|in:block,sector,road',
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        AreaNode::create($request->only('area_id','parent_id','type','name','is_active'));

        return back()->with('success','Node created successfully');
    }

    public function edit(AreaNode $areaNode)
    {
        $areas = Area::all();
        return view('area_nodes.edit', compact('areaNode','areas'));
    }

    public function update(Request $request, AreaNode $areaNode)
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'parent_id' => 'nullable|exists:area_nodes,id',
            'type' => 'required|in:block,sector,road',
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $areaNode->update($request->only('area_id','parent_id','type','name','is_active'));

        return redirect()->route('area-nodes.index')->with('success','Node updated successfully');
    }

    public function destroy(AreaNode $areaNode)
    {
        $areaNode->delete();
        return back()->with('success','Node deleted');
    }

    public function updateStatus(AreaNode $areaNode, $status)
    {
        $areaNode->update(['is_active'=>$status]);
        return back()->with('success','Status updated');
    }
}
