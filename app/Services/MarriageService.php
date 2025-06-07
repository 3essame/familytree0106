<?php

namespace App\Services;

use App\Models\FamilyTreeNode;
use App\Models\Marriage;
use Illuminate\Support\Facades\DB;

class MarriageService
{
    public function createMarriage(array $data): Marriage
    {
        return DB::transaction(function () use ($data) {            $marriage = Marriage::create([
                'person1_id' => $data['person1_id'],
                'person2_id' => $data['person2_id'],
                'marriage_date' => $data['marriage_date'],
                'notes' => $data['notes'] ?? null,
                'status' => 'active',
            ]);

            // Update any existing active marriages to divorced
            $this->updateExistingMarriages($data['person1_id'], $data['person2_id']);

            return $marriage;
        });
    }

    public function updateMarriageStatus(Marriage $marriage, string $status, ?string $divorceDate = null): Marriage
    {
        return DB::transaction(function () use ($marriage, $status, $divorceDate) {
            $marriage->update([
                'status' => $status,
                'divorce_date' => $divorceDate,
            ]);

            return $marriage;
        });
    }

    private function updateExistingMarriages(int $person1Id, int $person2Id): void
    {
        Marriage::where(function ($query) use ($person1Id, $person2Id) {
            $query->where('person1_id', $person1Id)
                ->orWhere('person2_id', $person1Id)
                ->orWhere('person1_id', $person2Id)
                ->orWhere('person2_id', $person2Id);
        })
        ->where('status', 'active')
        ->update([
            'status' => 'divorced',
            'divorce_date' => now(),
        ]);
    }

    public function getPersonMarriages(FamilyTreeNode $person): array
    {
        return [
            'active' => $person->marriages()->where('status', 'active')->get(),
            'past' => $person->marriages()->where('status', '!=', 'active')->get(),
        ];
    }
} 