<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->string('absence_type')->nullable()->after('is_present');
        });

        // existing absences predate leave, so they were all holidays taken
        DB::table('staff_attendances')->where('is_present', false)->update(['absence_type' => 'holiday']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table) {
            $table->dropColumn('absence_type');
        });
    }
};
