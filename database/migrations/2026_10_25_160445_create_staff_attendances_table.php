<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_present')->default(true);
            $table->unsignedTinyInteger('tasks_done')->default(0);
            $table->boolean('is_paid_absence')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['staff_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
    }
};
