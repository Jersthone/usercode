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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->foreignId('estado_id')->constrained('estados')->restrictOnDelete();
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();

            $table->index('sucursal_id');
            $table->index('estado_id');
        });

        DB::statement('ALTER TABLE pedidos ADD CONSTRAINT pedidos_total_check CHECK (total >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
