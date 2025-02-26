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
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign(['in_location_id'], 'fk_in_location_id_attendances_area_id')->references(['id'])->on('areas')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['out_location_id'], 'fk_out_location_id_attendances_area_id')->references(['id'])->on('areas')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['worker_id'], 'fk_worker_id_users_id')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign('fk_in_location_id_attendances_area_id');
            $table->dropForeign('fk_out_location_id_attendances_area_id');
            $table->dropForeign('fk_worker_id_users_id');
        });
    }
};
