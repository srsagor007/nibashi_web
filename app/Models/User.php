<?php

namespace App\Models;

use App\Concerns\BuildsQueries;
use App\Permissions\PermissionTrait;
use App\Services\FileUploadService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $userid RF id
 * @property string|null $user_code PSO code, DSM code etc
 * @property string|null $email
 * @property int|null $gender 1 - male, 2 - female, 3 - other
 * @property \Carbon\CarbonImmutable|null $dob
 * @property string|null $marital_status 1=>Married, 2=>Unmarried, 3=>Widowed
 * @property string|null $date_of_marriage
 * @property string|null $designation
 * @property string|null $supervisor_user_code
 * @property int|null $rsm_region_id FK: regions - id
 * @property string|null $rsm_region
 * @property int $is_active
 * @property int $is_superuser
 * @property int|null $primary_role_id `FK: roles - id`
 * @property int $points
 * @property string|null $hq
 * @property int|null $tbl_depot_id `FK: tbl_depot - id`
 * @property string|null $tbl_business_business_code `FK: tbl_business - business_code`
 * @property int|null $tbl_pso_user_type_id `FK: tbl_pso_user_type - id`
 * @property int $can_access_admin_panel
 * @property string|null $phone
 * @property string|null $photo
 * @property string|null $address
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string|null $password
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $last_login
 * @property string|null $device_token
 * @property string|null $device_data
 * @property string|null $user_meta
 * @property int|null $updated_by FK: users - id
 * @property int $is_password_changed 0 - Not changed, 1 - Changed
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\BuddyInfo|null $buddy_info
 * @property-read \App\Models\Business|null $business
 * @property-read \App\Models\Depot|null $depot
 * @property-read User|null $dsm
 * @property-read mixed $age
 * @property-read mixed $gender_text
 * @property-read mixed $name_with_code
 * @property-read mixed $name_with_userid
 * @property-read mixed $photo_url
 * @property-read \App\Models\PsoUserType|null $pso_dsm_user_type
 * @property-read \App\Models\Region|null $region
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read User|null $rsm
 * @property-read User|null $sm
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \App\Models\Role|null $user_type
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User staff()
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use BuildsQueries;
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use PermissionTrait;
    use SoftDeletes;

    protected $management_roles = ['admin', 'marketing', 'support']; // admin panel users

    protected $appends = [ 'photo_url'];

     protected $fillable = [
        'name',
        'userid',
        'email',
        'phone_number',
        'password',
        'present_address',
        'permanent_address',
        'dob',
        'nid',
        'photo',
        'nid_photo',
        'occupation',
        'office_name',
        'office_address',
        'refer_code',
        'refer_by',
        'merital_status',
        'gender',
        'email_verified_at',
        'is_active',
        'is_superuser',
        'is_password_changed',
        'can_access_admin_panel',
        'last_login',
        'primary_role_id',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'dob' => 'date',
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
        'is_active' => 'boolean',
        'is_superuser' => 'boolean',
        'is_password_changed' => 'boolean',
        'can_access_admin_panel' => 'boolean',
        'merital_status' => 'boolean',
    ];

    public function isAdmin()
    {
        if ($this->is_superuser) {
            return true;
        }

        if (! $this->relationLoaded('user_type')) {
            $this->load('user_type'); // lazy-load if not already loaded
        }

        if (in_array($this->user_type->slug, $this->management_roles)) {
            return true;
        }

        return false;
    }

    public function scopeStaff($query)
    {
        return $query->where('is_superuser', 0);
    }

    public function getGenderTextAttribute()
    {
        return [
            1 => 'Male',
            2 => 'Female',
        ][$this->gender] ?? 'Other';
    }

    public function getAgeAttribute()
    {
        return $this->dob ? Carbon::parse($this->dob)->diffInYears(Carbon::now()) : null;
    }

    public function getPhotoUrlAttribute()
    {
        if (empty($this->photo)) {
            return;
        }

        $fileUploadService = app(FileUploadService::class);

        return $fileUploadService->getFileUrl($this->photo);
    }

    // public function getPhotoUrlAttribute()
    // {
    //     if ($this->photo) {
    //         return Storage::url($this->photo);
    //     }

    //     return $this->noImageUrl();
    // }

    public function getNameWithUseridAttribute()
    {
        return "{$this->name} ($this->userid)";
    }

    public function getNameWithCodeAttribute()
    {
        return $this->user_code ? "{$this->name} ($this->user_code)" : $this->name;
    }

    // NOT Used. User user_type instead
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withPivot('is_primary');
    }

    public function user_type()
    {
        return $this->belongsTo(Role::class, 'primary_role_id');
    }

  
    public function buddy_info()
    {
        return $this->hasOne(BuddyInfo::class, 'rsm_user_id');
    }

    


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login' => 'datetime:Y-m-d h:i a',
            'dob' => 'date:Y-m-d',
        ];
    }

    
}
