<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('part1s', 'reference_number')) {
            return;
        }

        DB::table('part1s')
            ->where('reference_number', '')
            ->update(['reference_number' => null]);

        $duplicates = DB::table('part1s')
            ->select('reference_number')
            ->whereNotNull('reference_number')
            ->groupBy('reference_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('reference_number');

        foreach ($duplicates as $referenceNumber) {
            $rows = DB::table('part1s')
                ->where('reference_number', $referenceNumber)
                ->orderBy('id')
                ->pluck('id');

            foreach ($rows->skip(1) as $id) {
                DB::table('part1s')
                    ->where('id', $id)
                    ->update(['reference_number' => $referenceNumber . '-' . $id]);
            }
        }

        Schema::table('part1s', function (Blueprint $table) {
            $table->unique('reference_number', 'part1s_reference_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            $table->dropUnique('part1s_reference_number_unique');
        });
    }
};
