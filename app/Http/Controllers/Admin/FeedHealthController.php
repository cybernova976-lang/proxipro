<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Feed\FeedHealthReport;

/**
 * Écran /admin/feed-sante : état réel du flux par territoire.
 *
 * Lecture seule. Il remplace toute commande lancée à la main en production
 * pour mesurer le flux (offres, promotions, prestataires, demandes) et pour
 * préparer FEED_HIDDEN_AUTHOR_IDS.
 */
class FeedHealthController extends Controller
{
    public function __invoke(FeedHealthReport $report)
    {
        return response()
            ->view('admin.feed-health.index', ['pkHealth' => $report->build()])
            ->header('Cache-Control', 'private, no-store');
    }
}
