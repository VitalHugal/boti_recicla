<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\ZabbixJob;
use App\Models\Encrypt;
use App\Models\Product;
use App\Models\User;
use Doctrine\Common\Lexer\Token;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    protected $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function register(Request $request)
    {        
        DB::beginTransaction();
        try {

            $validated = $request->validate(
                $this->user->rules(),
                $this->user->feedback()
            );

            $cpf = $request->cpf;
            $name = $request->name;
            $email = $request->email;
            $notify = $request->notify;

            $hash = hash('sha256', $cpf);

            $user = User::where('cpf_hash', $hash)->first();

            if ($user) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF já registrado. Por favor, verifique.'
                ]);
            }

            $nameForValid = mb_strtolower($name);

            $blacklist = DB::table('blacklist')
                ->whereRaw('LOWER(name) LIKE ?', ["%{$nameForValid}%"])
                ->first();

            if ($blacklist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nome inválido. Por favor, verifique.'
                ]);
            };

            if ($validated) {

                if ($request->notify && $notify !== '' && $notify !== 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Válido apenas 1 para o campo aceito receber notificações.'
                    ]);
                }

                $data = [
                    'name'  => mb_strtoupper($name),
                    'email' => $email,
                    'cpf'   => $cpf,
                    'notify'   => $notify ?? null,
                ];

                $jsonData = json_encode($data);

                $e = new Encrypt;
                $encryptJsonData = $e->encrypt($jsonData);

                // $cpfHash = Hash::make($cpf);

                $cpfHash = hash('sha256', $cpf);

                $infoUser = User::create([
                    'info_user' => $encryptJsonData,
                    'cpf_hash' => $cpfHash,
                ]);

                if ($infoUser) {

                    $user = User::find($infoUser['id']);
                    $token = $user->createToken('UserToken')->plainTextToken;
                    $tokenFormatted = explode('|', $token)[1];


                    $result = ZabbixJob::dispatchSync();
                    Log::info(['resultado do envio ao zabbix' => $result]);

                    DB::commit();

                    Log::info("Registro de um novo usuario");
                    Log::info('id_user => ' . $infoUser['id']);

                    return response()->json([
                        'success' => true,
                        'message' => 'Registrado com sucesso.',
                        'data' => [
                            'id_user_register' => $infoUser['id'],
                            'token' =>  $tokenFormatted,
                        ],
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
}