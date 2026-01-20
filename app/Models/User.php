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

    protected $appends = ['name_with_code', 'name_with_userid', 'photo_url'];

    protected $fillable = [
        'name',
        'userid',
        'user_code',
        'email',
        'gender',
        'dob',
        'marital_status',
        'date_of_marriage',
        'designation',
        // 'supervisor_user_id', No need. delete this column
        'supervisor_user_code',
        'rsm_region_id',
        'rsm_region',
        'is_active',
        'is_superuser',
        'primary_role_id',
        'points',
        'hq',
        'tbl_depot_id',
        'tbl_business_business_code',
        'tbl_pso_user_type_id',
        'phone',
        'photo',
        'address',
        'password',
        'last_login',
        'can_access_admin_panel',
        'device_token',
        'device_data',
        'user_meta',
        'updated_by',
        'is_password_changed',
        'last_update_state',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'is_superuser',
        'can_access_admin_panel',
        'device_data',
        'deleted_at',
        'created_at',
        'updated_at',
        'user_meta',
        'updated_by',
        'email_verified_at',
        'last_update_state',
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

    public function dsm()
    {
        return $this->belongsTo(User::class, 'supervisor_user_code', 'user_code')
            ->where('primary_role_id', 6);
    }

    public function rsm()
    {
        return $this->belongsTo(User::class, 'supervisor_user_code', 'user_code')
            ->where('primary_role_id', 5);
    }

    public function sm()
    {
        return $this->belongsTo(User::class, 'supervisor_user_code', 'user_code')
            ->where('primary_role_id', 4);
    }

    public function buddy_info()
    {
        return $this->hasOne(BuddyInfo::class, 'rsm_user_id');
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class, 'tbl_depot_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'rsm_region_id');
    }

    public function pso_dsm_user_type()
    {
        return $this->belongsTo(PsoUserType::class, 'tbl_pso_user_type_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'tbl_business_business_code', 'business_code');
    }

    public function pso_doctors()
    {
        return $this->belongsToMany(DoctorInfo::class, 'tbl_pso_wise_doctor', 'user_code', 'tbl_doctor_info_id', 'user_code');
    }

    public function pso_exam_assigns()
    {
        return $this->hasMany(ExamAssign::class, 'user_id', 'id');
    }

    public function locations()
    {
        return $this->hasMany(UserLocation::class, 'user_id', 'id');
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

    public function pendingPsoTransfers()
    {
        return $this->hasMany(PsoTransfer::class, 'requested_for_user_id')
            ->where('status', 1); // pending status
    }
}
