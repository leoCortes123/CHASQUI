<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignUuid('servicio_id')->nullable()->constrained('servicios')->cascadeOnDelete();
            $table->enum('estado', ['activa', 'suspendida', 'vencida', 'cancelada'])->default('activa');
            $table->string('plan', 50)->nullable();
            $table->date('inicio');
            $table->date('fin')->nullable();
            $table->timestamps();

            $table->index('cliente_id', 'idx_suscripciones_cliente_id');
            $table->index('servicio_id', 'idx_suscripciones_servicio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
};
