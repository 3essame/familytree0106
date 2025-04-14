<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('membership_number')->unique();
            $table->string('phone');
            $table->string('email')->unique();
            $table->string('national_id')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('job_title')->nullable();
            $table->string('workplace')->nullable();
            $table->string('address')->nullable();
            $table->enum('subscription_status', ['paid', 'overdue', 'pending'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
