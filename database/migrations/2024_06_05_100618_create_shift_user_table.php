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
        Schema::create('shift_user', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('shift_id')->index('fk_shift_id_shift_users_shift');
            $table->unsignedBigInteger('worker_id')->index('fk_worker_id_shift_users_users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_user');
    }
};
