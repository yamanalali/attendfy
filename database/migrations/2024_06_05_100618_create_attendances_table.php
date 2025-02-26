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
        Schema::create('attendances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('worker_id')->nullable()->index('fk_worker_id_users_id');
            $table->date('date')->nullable();
            $table->date('date_out')->nullable();
            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();
            $table->time('work_hour')->nullable();
            $table->time('over_time')->nullable();
            $table->time('late_time')->nullable();
            $table->time('early_out_time')->nullable();
            $table->unsignedInteger('in_location_id')->nullable()->index('fk_in_location_id_attendances_area_id');
            $table->unsignedInteger('out_location_id')->nullable()->index('fk_out_location_id_attendances_area_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
