<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Quitar unique previo sobre key (nombre por convención)
            try {
                $table->dropUnique('settings_key_unique');
            } catch (Throwable $e) {
                // En SQLite puede fallar si no existe; lo ignoramos para entorno de tests
            }

            // Asegurar índice único compuesto por tenant y key
            if (! Schema::hasColumn('settings', 'tenant_id')) {
                // En caso de entornos sin la migración previa, lo agregamos de forma segura
                $table->foreignId('tenant_id')->nullable()->after('id');
            }

            try {
                $table->unique(['tenant_id', 'key']);
            } catch (Throwable $e) {
                // Ignorar si ya existe
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            try {
                $table->dropUnique(['tenant_id', 'key']);
            } catch (Throwable $e) {
            }
            try {
                $table->unique('key');
            } catch (Throwable $e) {
            }
        });
    }
};
