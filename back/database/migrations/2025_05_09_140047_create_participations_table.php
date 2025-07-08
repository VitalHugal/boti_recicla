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
        Schema::create('participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_user_id')->constrained('users')->onUpdate('cascade');
            $table->boolean('started_weighing')->default(null);
            $table->boolean('confirmation_weighing')->nullable()->default(null);
            $table->boolean('finished_weighing')->nullable();
            $table->boolean('finished_interaction')->nullable();
            $table->boolean('redeemed_credit')->nullable();
            $table->integer('credit')->nullable();
            $table->integer('trash_weight')->nullable();
            $table->boolean('over_700')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participations');
    }
};