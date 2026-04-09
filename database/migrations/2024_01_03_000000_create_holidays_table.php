<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('type');
            $table->string('status')->default('pending');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'start_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
