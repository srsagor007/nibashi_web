<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $menu_name
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppMenu query()
 */
class AppMenu extends Model
{
    protected $table = 'tbl_app_menu';

    protected $fillable = ['menu_name'];
}
