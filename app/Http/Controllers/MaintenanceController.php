<?php

namespace App\Http\Controllers;

use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MaintenanceController extends Controller
{
    public function resetSystem(Request $request)
    {
        abort_unless(
            auth()->user()?->role === 'admin',
            403,
            'Solo el administrador puede reiniciar el sistema.'
        );

        $data = $request->validate([
            'confirmation' => ['required', 'string'],
        ]);

        if ($data['confirmation'] !== 'REINICIAR SOTANO') {
            return back()->withErrors([
                'reset' => 'La frase de confirmación no es correcta.',
            ]);
        }

        $tablesToClean = [
            'command_batch_items',
            'command_batches',

            'order_item_modifiers',
            'order_payments',
            'order_items',
            'orders',

            'cash_movements',
            'cash_registers',

            'stock_movements',

            'purchase_items',
            'purchases',

            'activity_logs',

            'customers',
            'suppliers',
        ];

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::transaction(function () use ($tablesToClean) {
                foreach ($tablesToClean as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }

                // Conservar solo el usuario administrador.
                User::query()
                    ->where('email', '!=', 'admin@sotano.com')
                    ->delete();

                // Dejar todas las mesas libres.
                RestaurantTable::query()->update([
                    'status' => 'libre',
                    'reserved_by' => null,
                    'reserved_at' => null,
                    'is_active' => true,
                ]);

                // Reiniciar los IDs de las tablas limpiadas.
                if (Schema::hasTable('sqlite_sequence')) {
                    DB::table('sqlite_sequence')
                        ->whereIn('name', $tablesToClean)
                        ->delete();
                }
            });
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        return back()->with(
            'success',
            'Sistema reiniciado correctamente. Se conservaron productos, categorías, mesas, Barra, administrador y configuración.'
        );
    }
}
