<?php

namespace App\Jobs;

use App\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ZabbixJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            Log::info('Zabbix executado com sucesso');

            $users = User::count();

            $zabbix_server = 'fake-test.zabbix.com';
            $zabbix_port = 'test-port';
            $zabbix_key_boti = 'interactions_register';
            $hostname = 'nuc-lenovo-005w';

            // $cmd1 = "zabbix_sender -z $zabbix_server -p $zabbix_port -s \"$hostname\" -k \"$zabbix_key_boti\" -o $users";
            // exec($cmd1);
            
            $cmd = sprintf(
                'zabbix_sender -z %s -p %s -s %s -k %s -o %d',
                escapeshellarg($zabbix_server),
                escapeshellarg($zabbix_port),
                escapeshellarg($hostname),
                escapeshellarg($zabbix_key_boti),
                $users
            );

            // Executar comando e capturar saída e status
            exec($cmd, $output, $return_var);

            Log::info('Comando executado: ' . $cmd);
            Log::info('Saída do comando: ' . implode("\n", $output));
            Log::info('Código de retorno: ' . $return_var);
            
        } catch (\Throwable $t) {
            Log::error('Erro ao executar zabbixJob: ' . $t->getMessage());
        } catch (Exception $e) {
            Log::error('Erro zabbixJob: ' . $e->getMessage());
        }
    }
}