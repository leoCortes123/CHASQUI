<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejecuciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignUuid('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->foreignUuid('suscripcion_id')->nullable()->constrained('suscripciones')->nullOnDelete();
            $table->enum('estado', [
                'iniciada', 'en_progreso', 'esperando_confirmacion', 'completada', 'fallida', 'cancelada',
            ])->default('iniciada');
            $table->jsonb('contexto_json')->nullable();
            $table->text('error_mensaje')->nullable();
            $table->smallInteger('bloque_actual')->nullable();
            $table->enum('canal', ['telegram', 'whatsapp']);
            $table->string('mensaje_id_canal', 100)->nullable();
            $table->timestamps();

            $table->index('cliente_id', 'idx_ejecuciones_cliente_id');
            $table->index('servicio_id', 'idx_ejecuciones_servicio_id');
            $table->index('estado', 'idx_ejecuciones_estado');
            $table->index('created_at', 'idx_ejecuciones_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejecuciones');
    }
};
