<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_methods', 'whatsapp_number')) {
                $table->string('whatsapp_number')->nullable()->after('payment_url');
            }

            if (! Schema::hasColumn('payment_methods', 'whatsapp_template')) {
                $table->text('whatsapp_template')->nullable()->after('whatsapp_number');
            }

            if (! Schema::hasColumn('payment_methods', 'auto_redirect')) {
                $table->boolean('auto_redirect')->default(false)->after('whatsapp_template');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_type')) {
                $table->string('payment_type')->nullable()->after('payment_status');
            }

            if (! Schema::hasColumn('orders', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_type');
            }

            if (! Schema::hasColumn('orders', 'payment_redirect_url')) {
                $table->text('payment_redirect_url')->nullable()->after('payment_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            foreach ([
                'whatsapp_number',
                'whatsapp_template',
                'auto_redirect',
            ] as $column) {
                if (Schema::hasColumn('payment_methods', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            foreach ([
                'payment_type',
                'payment_reference',
                'payment_redirect_url',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};