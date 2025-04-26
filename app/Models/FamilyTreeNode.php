<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyTreeNode extends Model
{
    protected $fillable = [
        'name',
        'relation',
        'birth_date',
        'death_date',
        'gender',
        'notes',
        'info',
        'father_id',
        'mother_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
        'info' => 'array',
    ];

    public function father(): BelongsTo
    {
        return $this->belongsTo(FamilyTreeNode::class, 'father_id');
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(FamilyTreeNode::class, 'mother_id');
    }

    public function marriages(): HasMany
    {
        return $this->hasMany(Marriage::class, 'person1_id')
            ->orWhere('person2_id', $this->id);
    }

    public function currentSpouse()
    {
        return $this->marriages()
            ->where('status', 'active')
            ->first();
    }

    public function children(): HasMany
    {
        return $this->hasMany(FamilyTreeNode::class, 'father_id');
    }

    public function childrenAsMother(): HasMany
    {
        return $this->hasMany(FamilyTreeNode::class, 'mother_id');
    }

    public function siblings()
    {
        return FamilyTreeNode::where('father_id', $this->father_id)
            ->where('mother_id', $this->mother_id)
            ->where('id', '!=', $this->id)
            ->get();
    }

    public function paternalSiblings()
    {
        return FamilyTreeNode::where('father_id', $this->father_id)
            ->where('id', '!=', $this->id)
            ->get();
    }

    public function maternalSiblings()
    {
        return FamilyTreeNode::where('mother_id', $this->mother_id)
            ->where('id', '!=', $this->id)
            ->get();
    }

    public function uncles()
    {
        if (!$this->father) return collect();
        return $this->father->siblings();
    }

    public function aunts()
    {
        if (!$this->mother) return collect();
        return $this->mother->siblings();
    }
}
