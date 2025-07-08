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
        Schema::create('exits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_product_id')->constrained('products')->onUpdate('cascade');
            $table->foreignId('fk_participation_id')->constrained('participations')->onUpdate('cascade');
            $table->foreignId('fk_user_id')->constrained('users')->onUpdate('cascade');
            $table->integer('qtd');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exits');
    }
};