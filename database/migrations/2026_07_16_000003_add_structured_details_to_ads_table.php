<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->string('main_category', 100)->nullable()->after('category');
            $table->string('publication_domain', 40)->nullable()->after('main_category');
            $table->json('ad_details')->nullable()->after('publication_domain');
            $table->index('main_category', 'ads_main_category_idx');
            $table->index('publication_domain', 'ads_publication_domain_idx');
        });

        DB::table('ads')->whereNull('publication_domain')->update(['publication_domain' => 'service']);

        foreach (config('categories.services', []) as $mainCategory => $definition) {
            DB::table('ads')
                ->whereIn('category', $definition['subcategories'] ?? [])
                ->update([
                    'main_category' => $mainCategory,
                    'publication_domain' => 'service',
                ]);
        }

        foreach (config('categories.marketplace', []) as $mainCategory => $definition) {
            DB::table('ads')
                ->whereIn('category', $definition['subcategories'] ?? [])
                ->update([
                    'main_category' => $mainCategory,
                    'publication_domain' => config('ad_publication.category_domains.'.$mainCategory, 'service'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex('ads_main_category_idx');
            $table->dropIndex('ads_publication_domain_idx');
            $table->dropColumn(['main_category', 'publication_domain', 'ad_details']);
        });
    }
};
