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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('tenant_order_number')->nullable()->after('user_id');
            $table->foreignId('branch_id')->nullable()->after('tenant_order_number');
            $table->enum('delivery_type', ['takeaway', 'delivery'])->default('takeaway')->after('notes');
            $table->text('delivery_address')->nullable()->after('delivery_type');
        });

        $this->backfillTenantOrderNumbers();

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('tenant_order_number')->nullable(false)->change();
            $table->unique(['user_id', 'tenant_order_number']);

            $table->dropUnique(['order_number']);
            $table->dropColumn('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'tenant_order_number']);
            $table->dropColumn([
                'tenant_order_number',
                'branch_id',
                'delivery_type',
                'delivery_address',
            ]);

            $table->string('order_number')->nullable()->unique()->after('user_id');
        });
    }

    /**
     * Give every existing order a per-tenant number so the unique index can be
     * added without dropping rows that predate this migration.
     */
    protected function backfillTenantOrderNumbers(): void
    {
        $counters = [];

        DB::table('orders')
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->chunkById(200, function ($orders) use (&$counters): void {
                foreach ($orders as $order) {
                    $tenantId = (int) $order->user_id;

                    $counters[$tenantId] = ($counters[$tenantId] ?? 0) + 1;

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['tenant_order_number' => $counters[$tenantId]]);
                }
            });
    }
};
