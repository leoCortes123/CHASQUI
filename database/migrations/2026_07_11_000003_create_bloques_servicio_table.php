<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloques_servicio', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->smallInteger('orden');
            $table->string('alias', 100);
            $table->string('tipo_bloque', 100);
            $table->jsonb('config_json')->default('{}');
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['servicio_id', 'orden']);
            $table->unique(['servicio_id', 'alias']);
            $table->index(['servicio_id', 'orden'], 'idx_bloques_servicio_servicio_id_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloques_servicio');
    }
};
