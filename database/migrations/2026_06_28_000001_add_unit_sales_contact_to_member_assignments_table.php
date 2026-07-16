<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('member_assignments', 'unit_name')) {
                $table->string('unit_name')->nullable()->after('id');
            }
            if (! Schema::hasColumn('member_assignments', 'sales_associate')) {
                $table->string('sales_associate')->nullable()->after('agent_user_id');
            }
            if (! Schema::hasColumn('member_assignments', 'staff_contact')) {
                $table->string('staff_contact')->nullable()->after('sales_associate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('member_assignments', function (Blueprint $table) {
            $columns = array_filter(
                ['unit_name', 'sales_associate', 'staff_contact'],
                fn (string $column) => Schema::hasColumn('member_assignments', $column)
            );

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
