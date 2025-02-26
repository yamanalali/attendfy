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
        Schema::create('settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('app_name', 100)->nullable();
            $table->string('logo', 100)->nullable();
            $table->string('favicons', 200)->nullable();
            $table->string('color', 100)->nullable();
            $table->string('copyright', 100)->nullable();
            $table->text('key_app');
            $table->string('timezone', 200)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
