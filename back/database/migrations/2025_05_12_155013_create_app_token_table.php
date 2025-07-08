<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_token', function (Blueprint $table) {
            $table->id();
            $table->string('token')->max(255);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('app_token')->insert([
            'token' => 'dnBT23W4HxwYTHXJd8L2Rq7K3o0ggpzsBFktsKHNwOiD1uGEyHHGyD8eWLQKN7D1gZHwsTEagdTF87K8TvB1H0RX1iA1ZnuHwRh0WPWgrdPhPv8UA3chaWePEvTdypZ7En4apF5lcLWBRrLepBkHwLk0EePUauxsL3s8vzoBM5cttG0KlVHQ3wGa1uHxQVs4SA2IQ9NepradzOGytwNU0bocHciE9pdAi8Xgd6vbLDhxnstabtYF8fdo3a',
        ]);

        DB::table('app_token')->insert([
            'token' => 'VgZd06uei3ZSeJI2vEjTT2hLmoSzZok65Bu0woP2UzHngMGiM0IqqenNUkr5',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_token');
    }
};