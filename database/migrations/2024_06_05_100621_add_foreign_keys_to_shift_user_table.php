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
        Schema::table('shift_user', function (Blueprint $table) {
            $table->foreign(['shift_id'], 'fk_shift_id_shift_users_shift')->references(['id'])->on('shifts')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['worker_id'], 'fk_worker_id_shift_users_users')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_user', function (Blueprint $table) {
            $table->dropForeign('fk_shift_id_shift_users_shift');
            $table->dropForeign('fk_worker_id_shift_users_users');
        });
    }
};
