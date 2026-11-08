<?php

namespace Tests\Feature\Feed;

use App\Http\Controllers\FeedController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Lot 1, piste D4 : l'ancienne page d'essai /feed-test n'existe plus.
 */
class FeedTestRouteRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_test_page_returns_404(): void
    {
        $this->assertFalse(Route::has('feed.test'));
        $this->assertFalse(method_exists(FeedController::class, 'indexTest'));

        $this->get('/feed-test')->assertNotFound();
        $this->actingAs(User::factory()->create())->get('/feed-test')->assertNotFound();
    }
}
