<?php

namespace App\Console\Commands;

use App\Models\Encrypt;
use App\Models\Exits;
use App\Models\Participation;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AllExistsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:all_exists';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Schema::dropIfExists('all_exits');

        Schema::create('all_exits', function (Blueprint $table) {
            $table->id();
            $table->string('name')->max(255);
            $table->string('cpf');
            $table->string('email')->max(255);
            $table->string('name_product')->nullable()->max(255);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::beginTransaction();
        try {
            $users = User::all();

            foreach ($users as $user) {
                $info = $user->info_user;

                $deleted = $user->deleted_at ?? null;

                if (!$info) {
                    continue;
                }

                $d = new Encrypt();
                $resultDecrypt = $d->decrypt($info);
                $decoded = json_decode($resultDecrypt);

                if (!$decoded) {
                    continue;
                }

                $name = $decoded->name ?? null;
                $email = $decoded->email ?? null;
                $cpf = $decoded->cpf ?? null;

                $exits = Exits::where('fk_user_id', $user->id)->first();

                if ($exits) {
                    $product = Product::where('id', $exits->fk_product_id)->first();
                } else {
                   continue;
                }

                DB::table('all_exits')->insert([
                    'name' => $name,
                    'cpf' => $cpf,
                    'email' => $email,
                    'name_product' => $product != null ? $product->name : null,
                    'created_at' => ($exits->created_at ?? $user->created_at) ?? now(),
                    'updated_at' => ($exits->updated_at ?? $user->updated_at) ?? now(),
                    'deleted_at' => $deleted,
                ]);
            }

            DB::commit();
            $this->info('message: sucesso');
            return Command::SUCCESS;
        } catch (QueryException $qe) {
            Log::info('Error DB: ' . $qe->getMessage());
            $this->error('Erro no banco de dados.');
            return Command::FAILURE;
        } catch (Exception $e) {
            Log::info('Error: ' . $e->getMessage());
            $this->error('Erro inesperado: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}