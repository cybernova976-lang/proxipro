<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('status');
            $table->timestamp('publication_terms_accepted_at')->nullable()->after('expires_at');
            $table->string('publication_terms_version', 32)->nullable()->after('publication_terms_accepted_at');

            $table->index(['status', 'expires_at'], 'ads_status_expiry_idx');
            $table->index(['status', 'service_type', 'is_urgent', 'urgent_until'], 'ads_feed_urgent_idx');
            $table->index(['status', 'service_type', 'is_boosted', 'boost_end'], 'ads_feed_boost_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex('ads_status_expiry_idx');
            $table->dropIndex('ads_feed_urgent_idx');
            $table->dropIndex('ads_feed_boost_idx');
            $table->dropColumn([
                'expires_at',
                'publication_terms_accepted_at',
                'publication_terms_version',
            ]);
        });
    }
};
