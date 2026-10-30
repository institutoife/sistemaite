<?php
namespace App\Console\Commands;
use App\Services\CrmService;
use App\Services\GoogleContactsMigrationRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class PrepareCrm extends Command
{
    protected $signature='crm:prepare {--force : Confirmar migración en producción}';
    protected $description='Prepara CRM y vincula potenciales existentes sin exportar contactos ni enviar mensajes.';
    public function handle(GoogleContactsMigrationRunner $runner, CrmService $crm): int
    {
        $file=@fopen(storage_path('framework/crm-prepare.lock'),'c');
        if (!$file||!flock($file,LOCK_EX|LOCK_NB)) { if ($file) { fclose($file); } $this->error('Otra preparación del CRM está en curso o no se pudo abrir su bloqueo.'); return 1; }
        try { return $runner->withLegacyDateMode(DB::connection(),function () use ($crm) {
            $code=$this->call('migrate',['--path'=>['database/migrations/2026_10_29_000001_extend_prospectos_crm.php','database/migrations/2026_10_30_000001_extend_crm_ownership_and_interests.php'],'--force'=>(bool)$this->option('force')]);
            if ($code!==0) { return $code; }
            $code=$this->call('db:seed',['--class'=>'CrmSeeder','--force'=>(bool)$this->option('force')]);
            if ($code!==0) { return $code; }
            $count=$crm->prepareLegacy();
            app(\App\Services\CrmHistoryBackfill::class)->run();
            $this->info('CRM preparado. Potenciales vinculados: '.$count.'. No se han solicitado sincronizaciones ni enviado mensajes.');
            return 0;
        }); } finally { flock($file,LOCK_UN); fclose($file); }
    }
}
