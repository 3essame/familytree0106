<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    /**
     * الخصائص التي يمكن تعيينها بشكل جماعي
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'national_id',
        'membership_number',
        'status',
        'job_title',
        'workplace',
        'address',
        'notes'
    ];

    /**
     * الخصائص التي يجب تحويلها إلى أنواع محددة
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
