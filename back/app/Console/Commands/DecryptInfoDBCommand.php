<?php

namespace App\Console\Commands;

use App\Models\Encrypt;
use App\Models\Participation;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DecryptInfoDBCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:decrypt-all-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrypting database information.';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        Schema::dropIfExists('info_ger');

        Schema::create('info_ger', function (Blueprint $table) {
            $table->string('id');
            $table->string('name')->max(255);
            $table->string('cpf');
            $table->string('email')->max(255);
            $table->string('notify')->nullable()->max(255);
            // $table->foreignId('fk_participation')->nullable()->constrained('participations')->onUpdate('cascade');
            // $table->boolean('redeemed_credit')->nullable();
            // $table->integer('credit')->nullable();
            // $table->integer('trash_weight')->nullable();
            // $table->boolean('over_700')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::beginTransaction();
        try {
            $users = User::withTrashed()->get();
            // $users = User::all();

            foreach ($users as $user) {
                $info = $user->info_user;
                
                $id = $user->id ?? null;

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
                $notify = $decoded->notify ?? null;

                DB::table('info_ger')->insert([
                    'id' => $id,
                    'name' => $name,
                    'cpf' => $cpf,
                    'email' => $email,
                    'notify' => $notify,
                    'created_at' => $user->created_at ?? now(),
                    'updated_at' => now(),
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