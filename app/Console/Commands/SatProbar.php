<?php

namespace App\Console\Commands;

use App\Services\Sat\SatHealthCheck;
use Illuminate\Console\Command;

class SatProbar extends Command
{
    protected $signature = 'sat:probar';

    protected $description = 'Verifica que el servicio web de la SAT responde y muestra el ambiente configurado';

    public function handle(SatHealthCheck $health): int
    {
        $this->line('Ambiente: <comment>'.config('sat.environment').'</comment>');
        $this->line('URL base: <comment>'.config('sat.base_url').'</comment>');

        $startedAt = hrtime(true);
        $up = $health->probe();
        $ms = (int) ((hrtime(true) - $startedAt) / 1_000_000);

        if ($up) {
            $this->info("Servicio web activo ({$ms} ms)");

            return self::SUCCESS;
        }

        $this->error("El servicio web de la SAT no respondió como se esperaba ({$ms} ms)");

        return self::FAILURE;
    }
}
