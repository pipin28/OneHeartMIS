<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('percentage_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('percentage_settings', 'mode')) {
                $table->string('mode', 50)->default('Monthly')->after('id');
            }
        });

        if ($this->indexExists('percentage_settings', 'percentage_settings_role_tier_unique')) {
            Schema::table('percentage_settings', function (Blueprint $table) {
                $table->dropUnique(['role', 'tier']);
            });
        }

        if (!$this->indexExists('percentage_settings', 'percentage_settings_mode_role_tier_unique')) {
            Schema::table('percentage_settings', function (Blueprint $table) {
                $table->unique(['mode', 'role', 'tier']);
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('percentage_settings', 'percentage_settings_mode_role_tier_unique')) {
            Schema::table('percentage_settings', function (Blueprint $table) {
                $table->dropUnique(['mode', 'role', 'tier']);
            });
        }

        Schema::table('percentage_settings', function (Blueprint $table) {
            $table->unique(['role', 'tier']);
            $table->dropColumn('mode');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $result = DB::select("PRAGMA index_list('{$table}')");
            foreach ($result as $row) {
                $name = $row->name ?? null;
                if ($name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($result);
    }
};
