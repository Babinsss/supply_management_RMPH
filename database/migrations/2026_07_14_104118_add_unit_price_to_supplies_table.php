<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitPriceToSuppliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('supplies', function (Blueprint $table) {
            // Adds the unit_price column as a decimal (e.g., 999999.99)
            $table->decimal('unit_price', 10, 2)->nullable()->after('quantity');
        });
    }

    public function down()
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
}
