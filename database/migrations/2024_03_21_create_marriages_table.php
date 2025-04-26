<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('marriages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person1_id')->constrained('family_tree_nodes')->onDelete('cascade');
            $table->foreignId('person2_id')->constrained('family_tree_nodes')->onDelete('cascade');
            $table->date('marriage_date');
            $table->date('divorce_date')->nullable();
            $table->string('marriage_location')->nullable();
            $table->json('witnesses')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'divorced', 'widowed'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('marriages');
    }
}; 