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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->max(255);
            $table->integer('credit');
            $table->integer('qtd');
            $table->timestamps();
        });

        DB::table('products')->insert([
            //PRODUTOS BOTI
            ['name' => mb_strtoupper('Arbo Perfume'), 'credit' => '224', 'qtd' => '100', 'created_at' => now()],
            ['name' => mb_strtoupper('Nativa SPA Uva'), 'credit' => '224', 'qtd' => '75', 'created_at' => now()],
            ['name' => mb_strtoupper('Nativa SPA Ameixa'), 'credit' => '224', 'qtd' => '75', 'created_at' => now()],
            ['name' => mb_strtoupper('Nativa SPA Quinoa'), 'credit' => '224', 'qtd' => '75', 'created_at' => now()],
            ['name' => mb_strtoupper('Creme Hidratante Botik Vitamina 75 C'), 'credit' => '224', 'qtd' => '75', 'created_at' => now()],

            //PRDUTOS PARCEIROS
            ['name' => mb_strtoupper('Colar Pablita'), 'credit' => '384', 'qtd' => '100', 'created_at' => now()],
            ['name' => mb_strtoupper('Mochila Bossapack'), 'credit' => '576', 'qtd' => '50', 'created_at' => now()],
            ['name' => mb_strtoupper('Luminaria É Pedra'), 'credit' => '960', 'qtd' => '20', 'created_at' => now()],

            //MÉDIA TIRAGEM
            ['name' => mb_strtoupper('Bárbara Muller Colar Tambaqui'), 'credit' => '4096', 'qtd' => '2', 'created_at' => now()],
            ['name' => mb_strtoupper('Bárbara Muller Colar Tucunaré'), 'credit' => '4096', 'qtd' => '2', 'created_at' => now()],
            ['name' => mb_strtoupper('Vaso Requena Vento'), 'credit' => '3200', 'qtd' => '1', 'created_at' => now()],
            ['name' => mb_strtoupper('Vaso Requena Pomba'), 'credit' => '3840', 'qtd' => '1', 'created_at' => now()],

            //EXPERIENCIAS
            ['name' => mb_strtoupper('Corte Proença'), 'credit' => '896', 'qtd' => '30', 'created_at' => now()],
            ['name' => mb_strtoupper('Adote um Coral'), 'credit' => '224', 'qtd' => '60', 'created_at' => now()],

            //ICONICOS
            ['name' => mb_strtoupper('Fone Alok'), 'credit' => '9600', 'qtd' => '3', 'created_at' => now()],
            ['name' => mb_strtoupper('Prancha Medina'), 'credit' => '25600', 'qtd' => '1', 'created_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};