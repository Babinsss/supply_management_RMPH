<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsVisibleToSuppliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('supplies', function (Blueprint $table) {
            // Adds the visibility toggle, defaulting to true (visible)
            $table->boolean('is_visible')->default(true)->after('ris_number');
        });
    }

    public function down()
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn('is_visible');
        });
    }
}
