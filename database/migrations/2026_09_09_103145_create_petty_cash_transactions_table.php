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
        Schema::create('petty_cash_transactions', function (Blueprint $table) {
            $table->id();
            
            // Transaction Details
            $table->enum('type', ['Replenishment', 'Expense']); // Is money coming in or going out?
            $table->decimal('amount', 10, 2); // Handles amounts up to 99,999,999.99
            $table->string('description'); // What was bought or why it was replenished
            $table->string('receipt_reference')->nullable(); // OR number from the hardware store/supermarket
            
            // Smart Linking
            // If they bought an item that goes into inventory, link it here
            $table->foreignId('supply_id')->nullable()->constrained('supplies')->nullOnDelete(); 
            
            // Accountability
            $table->foreignId('processed_by')->constrained('users')->cascadeOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_transactions');
    }
};