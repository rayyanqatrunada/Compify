<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            if (! Schema::hasColumn('newsletter_subscribers', 'status')) {
                $table->string('status')->default('subscribed')->after('source');
            }

            if (! Schema::hasColumn('newsletter_subscribers', 'unsubscribed_at')) {
                $table->timestamp('unsubscribed_at')->nullable()->after('subscribed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $columns = array_filter(['status', 'unsubscribed_at'], fn ($column) => Schema::hasColumn('newsletter_subscribers', $column));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
