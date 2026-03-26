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
        Schema::create('store_archive', function (Blueprint $table) {
            $table->id();
            $table->employee_id();
            $table->store_id();
            $table->first_name();
            $table->middle_name();
            $table->last_name();
            $table->suffixes();
            $table->contact_number();
            $table->birthdate();
            $table->gender();
            $table->position();
            $table->rating();
            $table->username();
            $table->password();
            $table->house_number();
            $table->purok();
            $table->barangay();
            $table->city();
            $table->province();
            $table->sss();
            $table->philhealth();
            $table->pagibig();
            $table->cash_advance();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_archive');
    }
};
