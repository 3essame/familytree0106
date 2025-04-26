<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('family_tree_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('relation')->nullable();
            $table->json('info')->nullable();
            $table->foreignId('father_id')->nullable()->constrained('family_tree_nodes')->onDelete('set null');
            $table->foreignId('mother_id')->nullable()->constrained('family_tree_nodes')->onDelete('set null');
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('family_tree_nodes');
    }
}; 