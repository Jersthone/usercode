<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->integer('stock')->default(0);
        });

        DB::statement('ALTER TABLE productos ADD CONSTRAINT productos_stock_check CHECK (stock >= 0)');

        DB::table('productos')->update(['stock' => 100]);

        DB::statement(<<<'SQL'
            UPDATE productos
            SET stock = stock - detalle.vendido
            FROM (
                SELECT producto_id, SUM(cantidad)::integer AS vendido
                FROM detalle_pedidos
                GROUP BY producto_id
            ) AS detalle
            WHERE productos.id = detalle.producto_id
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE productos DROP CONSTRAINT IF EXISTS productos_stock_check');

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }
};
