<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRisAndExpiryToSuppliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
    {
        Schema::table('supplies', function (Blueprint $table) {
            // ->nullable() is what makes them NOT required
            $table->string('ris_number')->nullable()->after('description');
            $table->date('expiry_date')->nullable()->after('ris_number');
        });
    }

    public function down()
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn(['ris_number', 'expiry_date']);
        });
    }
}
