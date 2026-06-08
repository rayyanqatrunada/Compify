<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'stock_reserved_at')) {
                $table->timestamp('stock_reserved_at')->nullable()->after('payment_redirect_url');
            }

            if (! Schema::hasColumn('orders', 'stock_restored_at')) {
                $table->timestamp('stock_restored_at')->nullable()->after('stock_reserved_at');
            }

            if (! Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('stock_restored_at');
            }

            if (! Schema::hasColumn('orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('paid_at');
            }

            if (! Schema::hasColumn('orders', 'payment_notification_payload')) {
                $table->json('payment_notification_payload')->nullable()->after('cancelled_at');
            }
        });

        $this->addIndexIfMissing('orders', 'orders_user_status_created_index', ['user_id', 'order_status', 'created_at']);
        $this->addIndexIfMissing('orders', 'orders_payment_status_created_index', ['payment_status', 'created_at']);
        $this->addIndexIfMissing('order_items', 'order_items_product_id_index', ['product_id']);
        $this->addIndexIfMissing('products', 'products_active_category_brand_index', ['is_active', 'category_id', 'brand_id']);
        $this->addIndexIfMissing('products', 'products_active_sort_index', ['is_active', 'sort_order']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('products', 'products_active_sort_index');
        $this->dropIndexIfExists('products', 'products_active_category_brand_index');
        $this->dropIndexIfExists('order_items', 'order_items_product_id_index');
        $this->dropIndexIfExists('orders', 'orders_payment_status_created_index');
        $this->dropIndexIfExists('orders', 'orders_user_status_created_index');

        Schema::table('orders', function (Blueprint $table) {
            $columns = array_filter([
                'stock_reserved_at',
                'stock_restored_at',
                'paid_at',
                'cancelled_at',
                'payment_notification_payload',
            ], fn (string $column) => Schema::hasColumn('orders', $column));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (method_exists(Schema::getFacadeRoot(), 'getIndexes')) {
            foreach (Schema::getIndexes($table) as $index) {
                if (($index['name'] ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            foreach (DB::select('PRAGMA index_list(' . DB::getPdo()->quote($table) . ')') as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'select count(1) as aggregate from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
