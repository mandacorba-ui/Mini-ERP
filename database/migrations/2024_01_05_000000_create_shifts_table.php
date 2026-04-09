<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('label');
            $table->timestamps();

            $table->index('date');
        });

        Schema::create('shift_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['shift_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_assignments');
        Schema::dropIfExists('shifts');
    }
};
