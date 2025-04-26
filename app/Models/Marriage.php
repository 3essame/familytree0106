<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Marriage extends Model
{
    protected $fillable = [
        'person1_id',
        'person2_id',
        'marriage_date',
        'divorce_date',
        'marriage_location',
        'witnesses',
        'notes',
        'status', // active, divorced, widowed
    ];

    protected $casts = [
        'marriage_date' => 'date',
        'divorce_date' => 'date',
        'witnesses' => 'array',
    ];

    public function person1(): BelongsTo
    {
        return $this->belongsTo(FamilyTreeNode::class, 'person1_id');
    }

    public function person2(): BelongsTo
    {
        return $this->belongsTo(FamilyTreeNode::class, 'person2_id');
    }
} 