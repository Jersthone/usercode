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
        Schema::create('detalle_pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2)->storedAs('(cantidad * precio_unitario)::numeric(12, 2)');
            $table->unique(['pedido_id', 'producto_id']);

            $table->index('producto_id');
        });

        DB::statement('ALTER TABLE detalle_pedidos ADD CONSTRAINT detalle_pedidos_cantidad_check CHECK (cantidad > 0)');
        DB::statement('ALTER TABLE detalle_pedidos ADD CONSTRAINT detalle_pedidos_precio_unitario_check CHECK (precio_unitario >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_pedidos');
    }
};
