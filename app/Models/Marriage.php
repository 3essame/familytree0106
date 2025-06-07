<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Marriage extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'person1_id',
        'person2_id',
        'marriage_date',
        'divorce_date',
        'documents',
        'notes',
        'status'
    ];protected $casts = [
        'marriage_date' => 'date:Y-m-d',
        'divorce_date' => 'date:Y-m-d',
        'documents' => 'array'
    ];

    public function person1(): BelongsTo
    {
        return $this->belongsTo(FamilyTreeNode::class, 'person1_id');
    }

    public function person2(): BelongsTo
    {
        return $this->belongsTo(FamilyTreeNode::class, 'person2_id');
    }

    /**
     * تحقق مما إذا كان الزواج نشطاً
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * تحقق مما إذا كان الزواج قد انتهى
     */
    public function isEnded(): bool
    {
        return in_array($this->status, ['divorced', 'widowed']);
    }

    /**
     * احصل على مدة الزواج بالسنوات
     */    public function getDurationInYears(): float
    {
        if (!$this->marriage_date) {
            return 0;
        }

        $endDate = $this->divorce_date ?? ($this->status === 'active' ? now() : null);
        if (!$endDate) {
            return 0;
        }

        return $this->marriage_date->diffInDays($endDate) / 365.25;
    }

    /**
     * احصل على قائمة الأحداث المهمة في الزواج مرتبة زمنياً
     */
    public function getTimeline(): array
    {
        $timeline = [];

        // إضافة تاريخ الزواج
        if ($this->marriage_date) {
            $timeline[] = [
                'event' => 'marriage',
                'date' => $this->marriage_date
            ];
        }

        // إضافة تاريخ الطلاق
        if ($this->divorce_date) {
            $timeline[] = [
                'event' => 'divorce',
                'date' => $this->divorce_date
            ];
        }

        // ترتيب الأحداث حسب التاريخ
        usort($timeline, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        return $timeline;
    }
}