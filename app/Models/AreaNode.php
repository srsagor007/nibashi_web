<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\CommonAttributes;

class AreaNode extends Model
{
    use SoftDeletes,CommonAttributes;

    protected $fillable = [
        'area_id',
        'parent_id',
        'type',
        'name',
        'is_active'
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function parent()
    {
        return $this->belongsTo(AreaNode::class,'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AreaNode::class,'parent_id');
    }

    // Get full path for breadcrumb
   public function fullPath()
    {
        $segments = [];

        // Add the area first
        $segments[] = $this->area->name . ' (Area)';

        // Recursive parent traversal
        $parents = [];
        $parent = $this->parent;
        while($parent){
            array_unshift($parents, $parent); // prepend to maintain hierarchy order
            $parent = $parent->parent;
        }

        // Add parents in order
        foreach($parents as $p){
            $segments[] = $p->name . ' (' . $p->type . ')';
        }

        // Finally, add current node
        if(!in_array($this, $parents)){
            $segments[] = $this->name . ' (' . $this->type . ')';
        }

        return implode(' → ', $segments);
    }

}
