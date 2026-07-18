<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('telefono', 20)->unique();
            $table->bigInteger('telegram_chat_id')->nullable()->unique();
            $table->string('nombre')->nullable();
            $table->enum('canal_principal', ['telegram', 'whatsapp'])->default('telegram');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('telefono', 'idx_clientes_telefono');
            $table->index('telegram_chat_id', 'idx_clientes_telegram_chat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
