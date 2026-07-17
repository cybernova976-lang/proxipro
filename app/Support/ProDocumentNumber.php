<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class ProDocumentNumber
{
    public static function next(int $userId, string $documentType): string
    {
        $type = match ($documentType) {
            'quote' => 'DEV',
            'invoice' => 'FAC',
            default => throw new \InvalidArgumentException('Type de document commercial inconnu.'),
        };
        $year = (int) now()->format('Y');

        $number = DB::transaction(function () use ($userId, $documentType, $year): int {
            DB::table('pro_document_sequences')->insertOrIgnore([
                'user_id' => $userId,
                'document_type' => $documentType,
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('pro_document_sequences')
                ->where('user_id', $userId)
                ->where('document_type', $documentType)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            $next = ((int) $sequence->last_number) + 1;

            DB::table('pro_document_sequences')
                ->where('id', $sequence->id)
                ->update(['last_number' => $next, 'updated_at' => now()]);

            return $next;
        }, 3);

        return sprintf('%s-%d-%06d-%04d', $type, $year, $userId, $number);
    }
}
