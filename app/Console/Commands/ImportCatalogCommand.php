<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCatalogCommand extends Command
{
    protected $signature = 'sotano:importar-catalogo';

    protected $description = 'Importa categorías y productos desde productos.sqlite';

    public function handle()
    {
        $database = database_path('productos.sqlite');

        if (!file_exists($database)) {
            $this->error('No existe database/productos.sqlite');
            return self::FAILURE;
        }

        DB::statement("ATTACH DATABASE '{$database}' AS origen");

        DB::beginTransaction();

        try {

            DB::table('products')->delete();
            DB::table('categories')->delete();

            DB::statement("
                INSERT INTO categories
                SELECT * FROM origen.categories
            ");

            DB::statement("
                INSERT INTO products
                SELECT * FROM origen.products
            ");

            DB::statement("
                DELETE FROM sqlite_sequence
                WHERE name IN ('products','categories')
            ");

            DB::statement("
                INSERT INTO sqlite_sequence(name,seq)
                SELECT 'categories',MAX(id)
                FROM categories
            ");

            DB::statement("
                INSERT INTO sqlite_sequence(name,seq)
                SELECT 'products',MAX(id)
                FROM products
            ");

            DB::commit();

            DB::statement("DETACH DATABASE origen");

            $this->info('✔ Catálogo importado correctamente.');

            return self::SUCCESS;

        } catch (\Throwable $e) {

            DB::rollBack();

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
