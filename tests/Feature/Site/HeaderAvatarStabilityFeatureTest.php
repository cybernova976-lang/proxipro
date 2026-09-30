<?php

namespace Tests\Feature\Site;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderAvatarStabilityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_header_reserves_avatar_dimensions_before_the_body_renders(): void
    {
        $user = User::factory()->create(['avatar' => 'avatars/header-test.jpg']);

        $response = $this->actingAs($user)->get(route('profile.show'));
        $response->assertOk();

        $html = $response->getContent();
        $document = new DOMDocument;
        $this->assertTrue($document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new DOMXPath($document);

        foreach ([
            'header-user-avatar' => [38, 38],
            'dropdown-user-avatar' => [44, 44],
        ] as $class => [$width, $height]) {
            $images = $xpath->query("//header//img[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]");
            $this->assertCount(1, $images, "Une seule image {$class} doit être dans le header.");
            $this->assertSame((string) $width, $images->item(0)->getAttribute('width'));
            $this->assertSame((string) $height, $images->item(0)->getAttribute('height'));
        }

        $this->assertSame(1, preg_match('/<head\b[^>]*>(.*?)<\/head>/si', $html, $headMatches));
        $bodyPosition = stripos($html, '<body');
        $this->assertNotFalse($bodyPosition);
        $this->assertLessThan($bodyPosition, stripos($html, '</head>'));

        $head = $headMatches[1];
        $this->assertMatchesRegularExpression('/\.header-user-avatar-frame\s*\{[^}]*\bwidth:\s*38px\s*;[^}]*\bheight:\s*38px\s*;[^}]*\boverflow:\s*hidden\s*;/s', $head);
        $this->assertMatchesRegularExpression('/\.header-user-avatar-frame\s*>\s*img\s*\{[^}]*\bwidth:\s*100%\s*;[^}]*\bheight:\s*100%\s*;[^}]*\bobject-fit:\s*cover\s*;/s', $head);
        $this->assertMatchesRegularExpression('/\.dropdown-user-avatar\s*\{[^}]*\bwidth:\s*44px\s*;[^}]*\bheight:\s*44px\s*;[^}]*\bobject-fit:\s*cover\s*;/s', $head);
    }
}
