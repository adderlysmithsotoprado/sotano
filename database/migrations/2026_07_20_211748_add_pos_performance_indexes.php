<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Acelera la búsqueda del pedido abierto de una mesa.
         */
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_orders_table_status
             ON orders (restaurant_table_id, status)'
        );

        /*
         * Acelera ventas por turno y Dashboard.
         */
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_orders_status_paid_at
             ON orders (status, paid_at)'
        );

        /*
         * Acelera la carga de productos de cada pedido.
         */
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_order_items_order_status
             ON order_items (order_id, status)'
        );

        /*
         * Acelera la detección de productos pendientes de comanda.
         */
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_order_items_order_command
             ON order_items (order_id, command_sent, status)'
        );

        /*
         * Acelera la búsqueda y agrupación de productos activos.
         */
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_products_active_category
             ON products (is_active, category_id)'
        );

        /*
         * Acelera los movimientos del Kardex por producto.
         */
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_stock_movements_product_date
             ON stock_movements (product_id, created_at)'
        );

        /*
         * Acelera la búsqueda de cajas abiertas.
         */
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_cash_registers_user_status
             ON cash_registers (user_id, status, opened_at)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS idx_orders_table_status'
        );

        DB::statement(
            'DROP INDEX IF EXISTS idx_orders_status_paid_at'
        );

        DB::statement(
            'DROP INDEX IF EXISTS idx_order_items_order_status'
        );

        DB::statement(
            'DROP INDEX IF EXISTS idx_order_items_order_command'
        );

        DB::statement(
            'DROP INDEX IF EXISTS idx_products_active_category'
        );

        DB::statement(
            'DROP INDEX IF EXISTS idx_stock_movements_product_date'
        );

        DB::statement(
            'DROP INDEX IF EXISTS idx_cash_registers_user_status'
        );
    }
};
