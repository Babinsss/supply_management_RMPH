<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIssuedByToDepartmentRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('department_requests', function (Blueprint $table) {
            $table->foreignId('issued_by')->nullable()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('department_requests', function (Blueprint $table) {
            $table->dropForeign(['issued_by']);
            $table->dropColumn('issued_by');
        });
    }
}
