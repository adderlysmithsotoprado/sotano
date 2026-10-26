<?php

use App\Http\Controllers\BusinessSettingController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommandController;
use App\Http\Controllers\CommandPrintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModifierController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RestaurantTableController;
use App\Http\Controllers\SalesHistoryController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\CashMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\MaintenanceController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/clientes', [CustomerController::class, 'index'])
    ->name('clientes');

    Route::post('/clientes', [CustomerController::class, 'store'])
    ->name('clientes.store');

    Route::put('/clientes/{customer}', [CustomerController::class, 'update'])
    ->name('clientes.update');

    Route::delete('/clientes/{customer}', [CustomerController::class, 'destroy'])
    ->name('clientes.destroy');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/ticket/order/{order}', [TicketController::class, 'show'])
        ->name('ticket.show');
    Route::post('/ticket/{order}/printed', [TicketController::class, 'markPrinted'])
        ->name('ticket.printed');




    Route::middleware('role:admin,cajero')->group(function () {
        Route::get('/kardex', [StockMovementController::class, 'index'])
            ->name('kardex');
        Route::post('/kardex/ajuste', [StockMovementController::class, 'adjust'])
            ->name('kardex.adjust');
        Route::get('/kardex/export', [StockMovementController::class, 'export'])
            ->name('kardex.export');

        Route::get('/ventas', fn () => Inertia::render('Ventas/Index'))
            ->name('ventas');

        Route::post('/caja/movimientos', [CashMovementController::class, 'store'])
            ->name('caja.movimientos.store');

        Route::get('/productos', [ProductController::class, 'index'])
            ->name('productos');

        Route::post('/productos', [ProductController::class, 'store'])
            ->name('productos.store');

        Route::put('/productos/{product}', [ProductController::class, 'update'])
            ->name('productos.update');

        Route::delete('/productos/{product}', [ProductController::class, 'destroy'])
            ->name('productos.destroy');

        Route::get('/categorias', [CategoryController::class, 'index'])
            ->name('categorias');

        Route::post('/categorias', [CategoryController::class, 'store'])
            ->name('categorias.store');

        Route::put('/categorias/{category}', [CategoryController::class, 'update'])
            ->name('categorias.update');

        Route::delete('/categorias/{category}', [CategoryController::class, 'destroy'])
            ->name('categorias.destroy');

        Route::get('/modificadores', [ModifierController::class, 'index'])
            ->name('modificadores');

        Route::post('/modificadores', [ModifierController::class, 'store'])
            ->name('modificadores.store');

        Route::put('/modificadores/{modifier}', [ModifierController::class, 'update'])
            ->name('modificadores.update');

        Route::delete('/modificadores/{modifier}', [ModifierController::class, 'destroy'])
            ->name('modificadores.destroy');

        Route::get('/caja', [CashRegisterController::class, 'index'])
            ->name('caja');

        Route::post('/caja/open', [CashRegisterController::class, 'open'])
            ->name('caja.open');

        Route::post('/caja/{cashRegister}/close', [CashRegisterController::class, 'close'])
            ->name('caja.close');

        Route::get('/ventas/historial/exportar',
            [SalesHistoryController::class, 'export']
            )->name('ventas.historial.export');

        Route::get('/ventas/historial', [SalesHistoryController::class, 'index'])
            ->name('ventas.historial');

        Route::get('/ventas/historial/{order}', [SalesHistoryController::class, 'show'])
            ->name('ventas.show');

        Route::get('/stock-bajo', [StockController::class, 'index'])
            ->name('stock.bajo');

        Route::get('/proveedores', [SupplierController::class, 'index'])
        ->name('proveedores');

        Route::post('/proveedores', [SupplierController::class, 'store'])
        ->name('proveedores.store');

        Route::put('/proveedores/{supplier}', [SupplierController::class, 'update'])
        ->name('proveedores.update');

        Route::delete('/proveedores/{supplier}', [SupplierController::class, 'destroy'])
        ->name('proveedores.destroy');

        Route::get('/compras', [PurchaseController::class, 'index'])
        ->name('compras');

        Route::post('/compras', [PurchaseController::class, 'store'])
        ->name('compras.store');

        Route::get('/compras/{purchase}', [PurchaseController::class, 'show'])
            ->name('compras.show');
        Route::get('/promociones', [PromotionController::class, 'index'])
            ->name('promociones');

        Route::post('/promociones', [PromotionController::class, 'store'])
            ->name('promociones.store');

        Route::put('/promociones/{promotion}', [PromotionController::class, 'update'])
            ->name('promociones.update');

        Route::delete('/promociones/{promotion}', [PromotionController::class, 'destroy'])
            ->name('promociones.destroy');
    });

    Route::middleware('role:admin,cajero,mozo')->group(function () {
        Route::get('/mesas', [RestaurantTableController::class, 'index'])
            ->name('mesas');

        Route::post('/mesas', [RestaurantTableController::class, 'store'])
            ->name('mesas.store');

        Route::get('/mesas/{mesa}/editar', [RestaurantTableController::class, 'edit'])
            ->name('mesas.edit');

        Route::put('/mesas/{mesa}', [RestaurantTableController::class, 'update'])
            ->name('mesas.update');

        Route::delete('/mesas/{mesa}', [RestaurantTableController::class, 'destroy'])
            ->name('mesas.destroy');

        Route::post('/mesas/{mesa}/clear-reservation', [RestaurantTableController::class, 'clearReservation'])
            ->name('mesas.clearReservation');
        Route::put('/mesas/{table}/move',
            [RestaurantTableController::class,'move'])
            ->name('mesas.move');
        Route::put('/mesas/{table}/merge', [RestaurantTableController::class, 'merge'])
            ->name('mesas.merge');

        Route::get('/pos/mesa/{mesa}', [PosController::class, 'show'])
            ->name('pos.show');

        Route::post('/pos/order/{order}/items', [PosController::class, 'addItem'])
            ->name('pos.items.add');

        Route::post('/pos/order/{order}/close', [PosController::class, 'close'])
            ->name('pos.close');

        Route::post('/pos/order/{order}/send-command', [PosController::class, 'sendCommand'])
            ->name('pos.command.send');

        Route::put('/pos/order/{order}/customer', [PosController::class, 'updateCustomer'])
            ->name('pos.order.customer');

        Route::get('/pos/{order}/dividir-cuenta', [PosController::class, 'splitBill'])
            ->name('pos.split-bill');
        Route::post('/pos/{order}/dividir-cuenta', [PosController::class, 'processSplitBill'])
            ->name('pos.split-bill.process');

        Route::post('/pos/item/{item}/increase', [PosController::class, 'increaseItem'])
            ->name('pos.items.increase');

        Route::post('/pos/item/{item}/decrease', [PosController::class, 'decreaseItem'])
            ->name('pos.items.decrease');

        Route::put('/pos/item/{item}/note', [PosController::class, 'updateItemNote'])
            ->name('pos.items.note');

        Route::delete('/pos/item/{item}', [PosController::class, 'removeItem'])
            ->name('pos.items.remove');
        Route::put('/pos/item/{item}/move-table', [PosController::class, 'moveItemToTable'])
            ->name('pos.items.move-table');

        Route::get('/pos/order/{order}/payment', [PosController::class, 'payment'])
        ->name('pos.payment');
        Route::get('/pos/order/{order}/precuenta', [PosController::class, 'preCuenta'])
        ->name('pos.precuenta');

        Route::post('/pos/order/{order}/cancel', [PosController::class, 'cancelOrder'])
        ->name('pos.order.cancel');

        Route::put('/pos/order/{order}/guest-name', [PosController::class, 'updateGuestName'])
        ->name('pos.order.guest-name');

    });

    Route::middleware('role:admin,cocina,bar')->group(function () {
        Route::get('/comandas', [CommandController::class, 'index'])
            ->name('comandas');

        Route::post('/comandas/item/{item}/preparando', [CommandController::class, 'preparing'])
            ->name('comandas.item.preparando');

        Route::post('/comandas/item/{item}/listo', [CommandController::class, 'ready'])
            ->name('comandas.item.listo');


        Route::get('/comandas/order/{order}/print/{area}', [CommandPrintController::class, 'show'])
            ->name('comandas.print');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])
            ->name('usuarios');


        Route::post('/usuarios', [UserController::class, 'store'])
            ->name('usuarios.store');

        Route::put('/usuarios/{user}', [UserController::class, 'update'])
            ->name('usuarios.update');

        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])
            ->name('usuarios.destroy');

        Route::get('/reportes', [ReportController::class, 'index'])
            ->name('reportes');

        Route::get('/configuracion', [BusinessSettingController::class, 'index'])
            ->name('configuracion');

        Route::put('/configuracion', [BusinessSettingController::class, 'update'])
            ->name('configuracion.update');
        Route::post('/configuracion/reiniciar-sistema', [MaintenanceController::class, 'resetSystem'])
            ->name('system.reset');
        });


        Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

        Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

        Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

        Route::get('/auditoria', [ActivityLogController::class, 'index'])
            ->name('auditoria');

        Route::get('/impresoras', [PrinterController::class, 'index'])
        ->name('impresoras');

        Route::post('/impresoras', [PrinterController::class, 'update'])
        ->name('impresoras.update');

        Route::post('/backup', [BackupController::class, 'backup'])
            ->name('backup');
        Route::get('/respaldos', [BackupController::class, 'index'])
            ->name('respaldos');
        Route::post('/respaldos/{file}/restore', [BackupController::class, 'restore'])
            ->name('respaldos.restore');
        Route::delete('/respaldos/{file}', [BackupController::class, 'destroy'])
            ->name('respaldos.destroy');
});

require __DIR__.'/auth.php';
