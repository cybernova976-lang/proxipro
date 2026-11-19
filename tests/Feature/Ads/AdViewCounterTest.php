<?php

namespace Tests\Feature\Ads;

use App\Models\Ad;
use App\Models\Setting;
use App\Models\User;
use App\Services\AdViewCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Vues d'annonce (refonte de l'offre payante, lot 2). La colonne ads.views
 * existait mais rien ne l'alimentait : les écrans affichaient donc un zéro
 * présenté comme une mesure. Les vues sont maintenant comptées, avec les
 * règles des vues de profil, pour qu'un prestataire puisse constater l'effet
 * d'une mise en avant sur un chiffre qu'il peut croire.
 */
class AdViewCounterTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_a_visit_counts_once_per_visitor_and_per_day(): void
    {
        $ad = $this->offer();
        $visitor = User::factory()->create();

        $this->actingAs($visitor)->withHeader('User-Agent', self::BROWSER)->get(route('ads.show', $ad))->assertOk();
        $this->actingAs($visitor)->withHeader('User-Agent', self::BROWSER)->get(route('ads.show', $ad))->assertOk();
        $this->assertSame(1, (int) $ad->fresh()->views);
        $this->assertSame(1, DB::table('ad_views')->where('ad_id', $ad->id)->count());

        // Un autre visiteur, puis le même le lendemain : deux vues de plus.
        $this->actingAs(User::factory()->create())->withHeader('User-Agent', self::BROWSER)->get(route('ads.show', $ad))->assertOk();
        $this->travel(1)->days();
        $nextDay = \Illuminate\Http\Request::create('/ads/'.$ad->id, 'GET', [], [], [], ['HTTP_USER_AGENT' => self::BROWSER]);
        $this->assertTrue(app(AdViewCounter::class)->record($ad, $visitor, $nextDay));
        $this->assertFalse(app(AdViewCounter::class)->record($ad, $visitor, $nextDay));
        $this->assertSame(3, (int) $ad->fresh()->views);
    }

    public function test_the_author_robots_and_hidden_ads_are_never_counted(): void
    {
        $ad = $this->offer();

        // L'auteur regarde sa propre annonce.
        $this->actingAs($ad->user)->withHeader('User-Agent', self::BROWSER)->get(route('ads.show', $ad))->assertOk();
        // Un robot d'indexation.
        $this->actingAs(User::factory()->create())->withHeader('User-Agent', 'Googlebot/2.1 (+http://www.google.com/bot.html)')
            ->get(route('ads.show', $ad))->assertOk();
        $this->assertSame(0, (int) $ad->fresh()->views);

        // Une annonce archivée, consultée par son auteur ou par un administrateur.
        $ad->update(['status' => 'archived']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))->withHeader('User-Agent', self::BROWSER)
            ->get(route('ads.show', $ad))->assertOk();
        $this->assertSame(0, (int) $ad->fresh()->views);
        $this->assertSame(0, DB::table('ad_views')->count());
    }

    public function test_an_anonymous_visitor_is_counted_without_storing_an_address(): void
    {
        $ad = $this->offer();

        $counter = app(AdViewCounter::class);
        $request = \Illuminate\Http\Request::create('/ads/'.$ad->id, 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.9', 'HTTP_USER_AGENT' => self::BROWSER]);
        $this->assertTrue($counter->record($ad, null, $request));
        $this->assertFalse($counter->record($ad, null, $request));

        $row = DB::table('ad_views')->first();
        $this->assertNull($row->viewer_user_id);
        $this->assertStringStartsWith('a:', $row->viewer_key);
        $this->assertStringNotContainsString('203.0.113.9', json_encode($row));
    }

    public function test_my_ads_shows_the_measured_views_with_the_date_counting_started(): void
    {
        $this->travelTo('2026-11-25 10:00:00');
        Setting::set('ad_views_counted_since', '2026-11-19', 'statistics');
        $owner = User::factory()->create();
        $older = $this->offer(['user_id' => $owner->id, 'title' => 'Offre ancienne', 'created_at' => '2026-10-01 08:00:00', 'views' => 12]);
        $recent = $this->offer(['user_id' => $owner->id, 'title' => 'Offre récente', 'created_at' => '2026-11-22 08:00:00', 'views' => 1]);

        $this->actingAs($owner)->get(route('ads.myads'))->assertOk()
            // Annonce plus ancienne que le comptage : la date du début du comptage.
            ->assertSee('12 vues depuis le 19/11/2026')
            // Annonce publiée ensuite : sa date de publication.
            ->assertSee('1 vue depuis le 22/11/2026')
            ->assertDontSee('vues cumulées');

        $this->assertSame('2026-11-19', app(AdViewCounter::class)->countedSince($older)->toDateString());
        $this->assertSame('2026-11-22', app(AdViewCounter::class)->countedSince($recent)->toDateString());
    }

    public function test_no_figure_is_shown_while_counting_has_not_started(): void
    {
        Setting::where('key', 'ad_views_counted_since')->delete();
        \Illuminate\Support\Facades\Cache::flush();
        $owner = User::factory()->create();
        $this->offer(['user_id' => $owner->id]);

        $this->actingAs($owner)->get(route('ads.myads'))->assertOk()
            ->assertDontSee('vue depuis le')->assertDontSee('vues depuis le');
    }

    public function test_old_view_details_are_pruned_but_the_total_stays(): void
    {
        $ad = $this->offer(['views' => 2]);
        DB::table('ad_views')->insert([
            ['ad_id' => $ad->id, 'viewer_key' => 'u:1', 'viewed_on' => now()->subMonths(14)->toDateString(), 'created_at' => now()],
            ['ad_id' => $ad->id, 'viewer_key' => 'u:2', 'viewed_on' => now()->subDays(3)->toDateString(), 'created_at' => now()],
        ]);

        $this->artisan('profile-views:prune', ['--months' => 13])->assertSuccessful();

        $this->assertSame(1, DB::table('ad_views')->count());
        $this->assertSame(2, (int) $ad->fresh()->views);
        $this->assertSame(1, app(AdViewCounter::class)->countSince($ad, now()->subDays(30)));
    }

    private function offer(array $attributes = []): Ad
    {
        $author = isset($attributes['user_id']) ? null : User::factory()->create();
        $created = $attributes['created_at'] ?? null;
        unset($attributes['created_at']);

        $ad = Ad::create(array_merge(['user_id' => $author?->id, 'title' => 'Service de plomberie',
            'description' => 'Installation et dépannage de sanitaires.', 'category' => 'Plombier',
            'city' => 'Mamoudzou', 'country' => 'Mayotte', 'location' => 'Mamoudzou',
            'service_type' => 'offre', 'status' => 'active', 'visibility' => 'public'], $attributes));
        if (array_key_exists('views', $attributes)) {
            $ad->forceFill(['views' => $attributes['views']])->save();
        }
        if ($created) {
            $ad->forceFill(['created_at' => $created])->save();
        }

        return $ad->fresh();
    }
}
