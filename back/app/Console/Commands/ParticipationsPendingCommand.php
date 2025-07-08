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

class ParticipationsPendingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:participations_pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando para pegar as participaçãoes pendentes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Schema::dropIfExists('participation_pendings');

        Schema::create('participation_pendings', function (Blueprint $table) {
            $table->string('id');
            $table->string('name')->max(255);
            $table->string('cpf');
            $table->string('email')->max(255);
            $table->foreignId('fk_participation')->nullable()->constrained('participations')->onUpdate('cascade');
            $table->boolean('redeemed_credit')->nullable();
            $table->integer('credit')->nullable();
            $table->integer('trash_weight')->nullable();
            $table->boolean('over_700')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::beginTransaction();
        try {
            $name = null;

            $participations = Participation::with([
                'user' => function ($query) {
                    $query->whereNull('deleted_at');
                },
            ])
                ->whereNull('redeemed_credit')
                ->where('over_700', 1)
                ->where('trash_weight', '>=', 700)
                ->where('finished_interaction', 1)
                ->orderBy('created_at', 'desc')
                ->get()
                ->filter(function ($participation) use ($name) {
                    if ($name) {
                        $decrypt = new Encrypt();
                        $dataDecrypt = $decrypt->decrypt($participation->user->info_user);
                        $userData = json_decode($dataDecrypt);
                        return str_contains(strtolower($userData->name), strtolower($name));
                    }
                    return true;
                })
                ->values();

            if (!$participations && $name || $participations->isEmpty() && $name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado.'
                ]);
            }

            if (!$participations || $participations->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma participação encontrada.'
                ]);
            }

            $participations = $participations->transform(function ($participation) {

                $decrypt = new Encrypt();
                $dataDecrypt = json_decode($decrypt->decrypt($participation->user->info_user));

                DB::table('participation_pendings')->insert([
                    'id' => $participation->fk_user_id ?? null,
                    'name' => $dataDecrypt->name ?? null,
                    'cpf' => $dataDecrypt->cpf ?? null,
                    'email' => $dataDecrypt->email ?? null,
                    'fk_participation' => $participation->id ?? null,
                    'redeemed_credit' => $participation->redeemed_credit ?? null,
                    'credit' => $participation->credit ?? null,
                    'trash_weight' => $participation->trash_weight ?? null,
                    'created_at' => $participation->created_at ?? null,
                ]);
            });

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