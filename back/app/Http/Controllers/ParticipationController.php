<?php

namespace App\Http\Controllers;

use App\Jobs\ZabbixJob;
use App\Models\Encrypt;
use App\Models\Exits;
use App\Models\Participation;
use App\Models\Service\Utils;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ParticipationController extends Controller
{
    protected $participation;

    public function __construct(Participation $participation)
    {
        $this->participation = $participation;
    }

    //check-queues
    public function participationActive()
    {
        try {

            $idParticipation = Participation::with([
                'user' => function ($query) {
                    $query->whereNull('deleted_at');
                },
            ])
                ->orderBy('id', 'desc')
                ->first();

            if ($idParticipation === null || !$idParticipation->user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Disponível para iniciar pesagem.',
                ]);
            }

            $participationStart = $idParticipation->started_weighing;
            $participationEnd = $idParticipation->finished_interaction;

            // $idParticipationId =  $idParticipation->id;

            if ($participationStart == 1 && $participationEnd == null) {

                $d = new Encrypt();
                $dataDecrypt = $d->decrypt($idParticipation->user->info_user);

                $nameUser = json_decode($dataDecrypt)->name;

                return response()->json([
                    'success' => true,
                    'message' => 'Pesagem em andamento.',
                    'data' => [
                        // 'id_participation' => $idParticipationId,
                        'id' => $idParticipation->fk_user_id,
                        'name' => $nameUser,
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Disponível para iniciar pesagem.',
                ]);
            }
        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->errors(),
            ]);
        } catch (QueryException $qe) {

            Log::info('Error DB: ' . $qe->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo inesperado aconteceu. Tente novamente mais tarde.',
            ]);
        } catch (Exception $e) {

            Log::info('Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo aconteceu. Tente novamente mais tarde.',
            ]);
        }
    }

    //check-finish-weight
    public function checkFinishedParticipation($id)
    {
        try {

            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma usuário encontrado com id informado. Por favor, verifique.'
                ]);
            }

            $participation = Participation::where('fk_user_id', $id)->first();

            if (!$participation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma participação encontrada com id informado. Por favor, verifique.'
                ]);
            }

            $data = null;

            if ($participation->started_weighing === 1 && $participation->finished_weighing === null) {
                $data = false;
            } elseif ($participation->started_weighing === 1 && $participation->finished_weighing === 1) {
                $data = true;
            }

            if ($participation->started_weighing === 1 && $participation->finished_weighing === 1 && $participation->finished_interaction === 1 && $participation->credit === null && $participation->trash_weight === null) {
                $data = null;
            }

            return response()->json([
                'success' => true,
                'message' => 'Dados recuprados com sucesso.',
                'data' => $data
            ]);
        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->errors(),
            ]);
        } catch (QueryException $qe) {

            Log::info('Error DB: ' . $qe->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo inesperado aconteceu. Tente novamente mais tarde.',
            ]);
        } catch (Exception $e) {

            Log::info('Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo aconteceu. Tente novamente mais tarde.',
            ]);
        }
    }

    //start-weghing
    public function initialWeghing(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum resultado encontrado para o id informado. Por favor, verifique.'
                ]);
            }

            $validated = $request->validate(
                $this->participation->initialParticipationRules(),
                $this->participation->initialParticipationFeedback()
            );

            if ($validated) {

                $participation = Participation::where('fk_user_id', $id)->first();

                if ($participation) {

                    $startParticipation = $participation ? $participation->started_weighing : null;
                    $endParticipation = $participation ? $participation->finished_interaction : null;

                    if ($startParticipation === 1 && $endParticipation == null) {
                        return response()->json([
                            'success' => false,
                            'message' => "Participação em andamento.",
                            'data' => [
                                'idParticipation' => $participation->id
                            ],
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => "Usuário já participou",
                            'data' => [
                                'idParticipation' => $participation->id
                            ],
                        ]);
                    }
                }

                $idParticipation = Participation::orderBy('id', 'desc')->first();

                if ($idParticipation !== null) {

                    $startParticipation = $idParticipation ? $idParticipation->started_weighing : null;
                    $endParticipation = $idParticipation ? $idParticipation->finished_interaction : null;

                    if ($startParticipation === 1 && $endParticipation == null) {
                        return response()->json([
                            'success' => false,
                            'message' => "Participação em andamento.",
                            'data' => [
                                'idParticipation' => $idParticipation->id
                            ],
                        ]);
                    }
                }

                $initialParticipation = Participation::create([
                    'fk_user_id' => $id,
                    'started_weighing' => $request->started_weighing,
                ]);

                if ($initialParticipation) {

                    DB::commit();

                    Log::info('Pesagem iniciada');
                    Log::info('id_participation => ' . $initialParticipation['id']);
                    Log::info('id_user_participation => ' . $user->id);

                    return response()->json([
                        'success' => true,
                        'message' => 'Pesagem iniciada com sucesso.',
                        'data' => [
                            'id_participation' => $initialParticipation['id'],
                            'id_user_participation' => $user->id,
                        ]
                    ]);
                }
            }
        } catch (ValidationException $ve) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $ve->errors(),
            ]);
        } catch (QueryException $qe) {

            Log::info('Error DB: ' . $qe->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo inesperado aconteceu. Tente novamente mais tarde.',
            ]);
        } catch (Exception $e) {

            Log::info('Error: ' . $e->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo aconteceu. Tente novamente mais tarde.',
            ]);
        }
    }

    //finish-weghing
    public function finishedWeghing(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum usuário encontrado para o id informado. Por favor, verifique.'
                ]);
            }

            $validated = $request->validate(
                $this->participation->finishedParticipationRules(),
                $this->participation->finishedParticipationFeedback()
            );

            if ($validated) {

                $paticipationUser = Participation::where('fk_user_id', $id)->first();

                if ($paticipationUser === null) {

                    $startParticipation = $paticipationUser ? $paticipationUser->started_weighing : null;

                    if (!$startParticipation == 1) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Usuário informado não tem nenhuma pesagem para finalizar.'
                        ]);
                    }
                }

                if ($paticipationUser->finished_weighing === 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A pesagem desse usuário já foi finalizada.'
                    ]);
                }

                if ($paticipationUser->started_weighing == 1 && !$paticipationUser->confirmation_weighing == 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Não é possível finalizar a pesagem pois ainda não foi confirmada pelo totem.'
                    ]);
                }

                $paticipationUser->update([
                    'finished_weighing' => $request->finished_weighing,
                ]);

                if ($paticipationUser) {

                    DB::commit();

                    Log::info('Pesagem finalizada');
                    Log::info('id_participation => ' . $paticipationUser->id);

                    return response()->json([
                        'success' => true,
                        'message' => 'Pesagem finalizada com sucesso.',
                    ]);
                }
            }
        } catch (ValidationException $ve) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $ve->errors(),
            ]);
        } catch (QueryException $qe) {

            Log::info('Error DB: ' . $qe->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo inesperado aconteceu. Tente novamente mais tarde.',
            ]);
        } catch (Exception $e) {

            Log::info('Error: ' . $e->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo aconteceu. Tente novamente mais tarde.',
            ]);
        }
    }

    //check-results
    public function getResultsParticipation(Request $request, $id)
    {
        try {

            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum usuário encontrado com id informado. Por favor, verifique.',
                ]);
            }

            // if ($user->deleted_at) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Tempo excedido.',
            //     ]);
            // }

            $resultsParticipation = Participation::where('fk_user_id', $id)->first();

            if (!$resultsParticipation || !$resultsParticipation->user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma participação encontrada para o id informado. Por favor, verifique.'
                ]);
            }


            $d = new Encrypt();
            $dataDecrypt = $d->decrypt($resultsParticipation->user->info_user);

            $nameUser = json_decode($dataDecrypt)->name;

            return response()->json([
                'success' => true,
                'message' => 'Dados recuperados com sucesso.',
                'data' => [
                    'name' => $nameUser,
                    // 'finished_interaction' => $resultsParticipation->finished_interaction === 1 ? true : false,
                    'credits' => $resultsParticipation->credit,
                    'weight' => $resultsParticipation->trash_weight,
                ]
            ]);
        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->errors(),
            ]);
        } catch (QueryException $qe) {

            Log::info('Error DB: ' . $qe->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo inesperado aconteceu. Tente novamente mais tarde.',
            ]);
        } catch (Exception $e) {

            Log::info('Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo aconteceu. Tente novamente mais tarde.',
            ]);
        }
    }

    //send-results
    public function sendResultParticipationAndFinishedInteraction(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum usuário encontrado para o id informado. Por favor, verifique.'
                ]);
            }

            $participation = Participation::where('fk_user_id', $id)->first();

            if (!$participation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma participação encontrado para o id informado. Por favor, verifique.'
                ]);
            }

            if ($participation->finished_interaction === 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Essa participação já foi finalizada.'
                ]);
            }

            $validated = $request->validate(
                $this->participation->resultParticipationRules(),
                $this->participation->resultParticipationFeedback()
            );

            if ($validated) {

                $participation->update([
                    'credit' => $request->credits,
                    'trash_weight' => $request->weight,
                    'over_700' => $request->weight >= 700 ? 1 : 0,
                    // 'finished_interaction' => 1,
                ]);
            }

            if ($participation) {

                DB::commit();

                Log::info("Creditos recebidos");
                Log::info("id_participation => " . $participation->id);
                Log::info("credits => " . $participation->credits);

                return response()->json([
                    'success' => true,
                    'message' => 'Dados registrados com sucesso.',
                ]);
            }
        } catch (ValidationException $ve) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $ve->errors(),
            ]);
        } catch (QueryException $qe) {

            Log::info('Error DB: ' . $qe->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo inesperado aconteceu. Tente novamente mais tarde.',
            ]);
        } catch (Exception $e) {

            Log::info('Error: ' . $e->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo aconteceu. Tente novamente mais tarde.',
            ]);
        }
    }

    //check-credits
    public function checkCreditsAllParticipation(Request $request)
    {
        try {

            $name = $request->input('name');

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
                    return true; // sem filtro → retorna tudo
                })
                ->values(); // reindexa os resultados

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

                return [
                    'id' => $participation->fk_user_id ?? null,
                    'name' => $dataDecrypt->name ?? null,
                    'identification' => $dataDecrypt->cpf ?? null,
                    'credits' => $participation->credit ?? null,
                    'weight' => $participation->trash_weight ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Dados recuperados com sucesso.',
                'data' => $participations,
            ]);

            // // $name = $request->input('name');

            // $participations = Participation::with([
            //     'user' => function ($query) {
            //         $query->whereNull('deleted_at');
            //             // ->when($name != '', function ($subQuery) use ($name) {
            //             //     $subQuery->whereHas('user', function ($q) use ($name) {
            //             //         $q->where('name', 'like', '%' . $name . '%');
            //             //     });
            //             // });
            //     },
            // ])
            //     ->whereNull('redeemed_credit')
            //     ->where('over_700', 1)
            //     ->where('trash_weight', '>=', 700)
            //     ->orderBy('id', 'desc')
            //     ->get();

            // $participations->transform(function ($participation) use ($request) {

            //     if ($request->has("name") && $request->input("name") != '') {
            //         # code...
            //     }

            //     $d = new Encrypt();
            //     $dataDecrypt = $d->decrypt($participation->user->info_user);

            //     $nameUser = json_decode($dataDecrypt)->name;
            //     $cpfUser = json_decode($dataDecrypt)->cpf;

            //     return [
            //         'id' => $participation->fk_user_id ?? null,
            //         'name' => $nameUser ?? null,
            //         'identification' => $cpfUser ?? null,
            //         'credits' => $participation->credit ?? null,
            //         'weight' => $participation->trash_weight ?? null,
            //     ];
            // });

        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->errors(),
            ]);
        } catch (QueryException $qe) {

            Log::info('Error DB: ' . $qe->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo inesperado aconteceu. Tente novamente mais tarde.',
            ]);
        } catch (Exception $e) {

            Log::info('Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo aconteceu. Tente novamente mais tarde.',
            ]);
        }
    }

    //redeem
    public function redeemProducts(Request $request)
    {
        DB::beginTransaction();
        try {

            $id_user = null;
            $id_product = null;

            $id_user = $request->id_user;
            $id_product = $request->id_product;

            $user = User::where('id', $id_user)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum usuário encontrado para o id informado. Por favor, verifique.'
                ]);
            }

            $participation = Participation::where('fk_user_id', $id_user)
                ->where('started_weighing', 1)
                ->where('finished_weighing', 1)
                ->where('finished_interaction', 1)
                ->where('trash_weight', '>=', '700')
                ->first();

            if (!$participation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma participação encontrada para o id informado. Por favor, verifique.'
                ]);
            }

            if ($participation->redeemed_credit === 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário já recuperou um produto.'
                ]);
            }

            $creditsRecorvered = $participation->update([
                'redeemed_credit' => 1
            ]);

            $productQuantities = Utils::getProductQuantities($id_product);

            $qtdProduct = $productQuantities['qtdProduct'];
            $qtdExits = $productQuantities['qtdExits'];

            $quantityTotalProduct = $qtdProduct - $qtdExits;

            if ($quantityTotalProduct < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto indisponível.',
                ]);
            }

            if ($creditsRecorvered) {

                Exits::create([
                    'fk_user_id' => $id_user,
                    'fk_product_id' => $id_product,
                    'qtd' => 1,
                    'fk_participation_id' => $participation->id,
                ]);

                DB::commit();

                Log::info("Produto recuperado");
                Log::info("id_participation => " . $participation->id);
                Log::info("fk_user_id => " . $id_user);
                Log::info("fk_product_id => " . $id_product);

                return response()->json([
                    'success' => true,
                    'message' => 'Dados atualizado com sucesso.'
                ]);
            }
        } catch (ValidationException $ve) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $ve->errors(),
            ]);
        } catch (QueryException $qe) {

            Log::info('Error DB: ' . $qe->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo inesperado aconteceu. Tente novamente mais tarde.',
            ]);
        } catch (Exception $e) {

            Log::info('Error: ' . $e->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo aconteceu. Tente novamente mais tarde.',
            ]);
        }
    }

    //confirmation-weighing
    public function confirmationWeighing(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $participation = Participation::where('fk_user_id', $id)->first();

            if (!$participation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma participação encontrado para o id informado. Por favor, verifique.'
                ]);
            }

            $validated = $request->validate(
                $this->participation->confirmationWeighingRules(),
                $this->participation->confirmationWeighingFeedback()
            );

            if ($validated) {

                if ($request->confirmation === 1) {
                    $participation->update(['confirmation_weighing' => $request->confirmation]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'É necessário o valor ser igual a 1.',
                    ]);
                }
            }

            if ($participation) {
                DB::commit();

                Log::info("Confirmacao da pesagem");
                Log::info('id_participation => ' . $participation->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Pesagem confirmada.',
                ]);
            }
        } catch (ValidationException $ve) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $ve->errors(),
            ]);
        } catch (QueryException $qe) {

            Log::info('Error DB: ' . $qe->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo inesperado aconteceu. Tente novamente mais tarde.',
            ]);
        } catch (Exception $e) {

            Log::info('Error: ' . $e->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo aconteceu. Tente novamente mais tarde.',
            ]);
        }
    }

    //finish-interaction
    public function finishInteraction(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum usuário encontrado para o id informado. Por favor, verifique.'
                ]);
            }

            $participation = Participation::where('fk_user_id', $id)->first();

            if (!$participation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma participação encontrado para o id informado. Por favor, verifique.'
                ]);
            }

            if ($participation->finished_interaction === 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Essa participação já foi finalizada.'
                ]);
            }

            $validated = $request->validate(
                $this->participation->finishedInterationRules(),
                $this->participation->finishedInterationFeedback()
            );

            if ($validated) {

                $participation->update([
                    'finished_interaction' => 1,
                ]);
            }

            if ($participation) {

                DB::commit();

                Log::info("Finalizacao interacao do usuario");
                Log::info('id_participation => ' .  $participation->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Dados registrados com sucesso.',
                ]);
            }
        } catch (ValidationException $ve) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $ve->errors(),
            ]);
        } catch (QueryException $qe) {

            Log::info('Error DB: ' . $qe->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo inesperado aconteceu. Tente novamente mais tarde.',
            ]);
        } catch (Exception $e) {

            Log::info('Error: ' . $e->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo aconteceu. Tente novamente mais tarde.',
            ]);
        }
    }

    //finish-all
    public function finishAll(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $token = $request->bearerToken();

            $infoDB = DB::table('app_token')->where('id', 2)->first();

            if ($token !== $infoDB->token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $user = User::where('id', $id)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum usuário encontrado com o id informado. Por favor, verifique.',
                ]);
            }

            $participation = Participation::where('fk_user_id', $id)->first();

            if (!$participation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma participação encontrada. Por favor verifique.',
                ]);
            }

            $participation->update([
                'started_weighing' => 1,
                'finished_weighing' => 1,
                'confirmation_weighing' => 1,
                'finished_interaction' => 1,
            ]);

            $user->delete();

            DB::commit();

            Log::info("TIME-OUT");
            Log::info('id_participation => ' . $participation->id);

            return response()->json([
                'success' => true,
                'message' => 'Participação finalizada com sucesso.',
            ]);
        } catch (ValidationException $ve) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $ve->errors(),
            ]);
        } catch (QueryException $qe) {

            Log::info('Error DB: ' . $qe->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo inesperado aconteceu. Tente novamente mais tarde.',
            ]);
        } catch (Exception $e) {

            Log::info('Error: ' . $e->getMessage());
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ops, algo aconteceu. Tente novamente mais tarde.',
            ]);
        }
    }
}