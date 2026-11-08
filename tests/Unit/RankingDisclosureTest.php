<?php

namespace Tests\Unit;

use App\Services\Feed\RankingDisclosure;
use App\Support\TerritoryResolver;
use Tests\TestCase;

/**
 * La ligne de critère (D111-6 II) doit énoncer les critères dans l'ordre
 * réel du tri de ClientFeedOffersService : sponsorisées, puis catégories
 * suivies, puis date de publication.
 */
class RankingDisclosureTest extends TestCase
{
    public function test_line_without_followed_categories_states_sponsored_then_date(): void
    {
        $line = (new RankingDisclosure())->forClientFeed(app(TerritoryResolver::class)->find('976'));

        $this->assertSame(
            'Classement : offres de Mayotte. Les offres sponsorisées (payantes) passent en premier, puis les plus récentes.',
            $line['text']
        );
        $this->assertStringContainsString('Deux offres au plus par prestataire', $line['details']);
    }

    public function test_followed_categories_are_stated_before_the_date_criterion(): void
    {
        $line = (new RankingDisclosure())->forClientFeed(null, false, true);

        $this->assertSame(
            'Classement : offres de toutes les zones. Les offres sponsorisées (payantes) passent en premier, puis celles des catégories que vous suivez, puis les plus récentes.',
            $line['text']
        );
        $this->assertStringNotContainsString('À égalité', $line['details']);
    }
}
