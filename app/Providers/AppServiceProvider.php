<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        try {
            /*
             * WAL permite que SQLite lea mientras realiza escrituras.
             * Es útil cuando el POS consulta mesas, stock, comandas
             * y caja continuamente.
             */
            DB::statement('PRAGMA journal_mode = WAL');

            /*
             * Mantiene buena seguridad sin forzar una escritura completa
             * al disco en cada pequeña operación.
             */
            DB::statement('PRAGMA synchronous = NORMAL');

            /*
             * Espera hasta 5 segundos cuando la base está ocupada,
             * en lugar de fallar inmediatamente.
             */
            DB::statement('PRAGMA busy_timeout = 5000');

            /*
             * Usa memoria para ciertas operaciones temporales.
             */
            DB::statement('PRAGMA temp_store = MEMORY');

            /*
             * Mantiene activas las relaciones entre tablas.
             */
            DB::statement('PRAGMA foreign_keys = ON');
        } catch (\Throwable $error) {
            Log::warning(
                'No se pudieron aplicar las optimizaciones de SQLite.',
                [
                    'error' => $error->getMessage(),
                ]
            );
        }
    }
}