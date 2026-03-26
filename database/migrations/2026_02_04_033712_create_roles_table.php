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
        Schema::table('employees', function (Blueprint $table) {
            // Add role_id column after position column
            $table->unsignedBigInteger('role_id')->nullable()->after('position');
            
            // Add foreign key constraint
            $table->foreign('role_id')
                  ->references('id')
                  ->on('roles')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['role_id']);
            
            // Drop column
            $table->dropColumn('role_id');
        });
    }
};