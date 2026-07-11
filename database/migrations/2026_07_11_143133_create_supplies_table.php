<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('category', 100)->nullable();
            $table->string('description', 255)->nullable();
            $table->integer('quantity')->default(0);
            $table->string('unit', 50);
            $table->integer('reorder_level')->default(10);
            $table->timestamps(); // Automatically creates created_at and updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};