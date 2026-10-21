<?php

namespace App\Console\Commands;

use App\Services\ConstelacionService;
use Illuminate\Console\Command;

class CargarConstelacionCommand extends Command
{
    protected $signature = 'agrolink:cargar-constelacion';

    protected $description = 'Carga/actualiza las dimensiones y hechos (esquema constelación) desde las tablas transaccionales.';

    public function handle(ConstelacionService $servicio): int
    {
        $inicio = microtime(true);
        $conteos = $servicio->cargar();

        $this->table(['Tabla', 'Filas'], collect($conteos)->map(fn ($n, $t) => [$t, $n])->values()->all());
        $this->info(sprintf('Constelación actualizada en %.2f s.', microtime(true) - $inicio));

        return self::SUCCESS;
    }
}
