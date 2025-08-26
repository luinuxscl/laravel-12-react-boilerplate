<?php

use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'key']);
        });

        // Backfill al tenant default si existe
        if (Schema::hasTable('tenants')) {
            $defaultId = Tenant::query()->where('is_default', true)->value('id');
            if ($defaultId) {
                DB::table('settings')->whereNull('tenant_id')->update(['tenant_id' => $defaultId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropIndex(['tenant_id', 'key']);
        });
    }
};
