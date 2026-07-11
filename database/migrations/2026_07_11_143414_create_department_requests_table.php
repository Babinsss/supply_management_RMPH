<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('department_requests', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 50);
            $table->string('department_name', 100);
            $table->string('requested_by', 100);
            
            // Foreign key relation matching your supply_id link
            $table->foreignId('supply_id')->constrained('supplies')->onDelete('cascade');
            
            $table->integer('quantity');
            $table->string('purpose', 255);
            $table->string('status', 20)->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_requests');
    }
};