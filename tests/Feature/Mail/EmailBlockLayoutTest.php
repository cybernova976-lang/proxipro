<?php

namespace Tests\Feature\Mail;

use App\Mail\ManagedEmailMail;
use App\Models\ManagedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmailBlockLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    }

    private function setupComposer(): void
    {
        Mail::fake();
        Storage::fake(config('filesystems.default'));
        $this->actingAs(User::factory()->create(['role'=>'admin','newsletter_subscribed'=>false]));
        User::factory()->create(['newsletter_subscribed'=>true,'email_notifications'=>true,'is_active'=>true]);
    }

    public function test_layout_is_saved_rendered_in_order_and_editable(): void
    {
        $this->setupComposer();
        $blocks = [
            ['type'=>'image','ref'=>'new:0','width'=>80,'align'=>'left','caption'=>'Portrait'],
            ['type'=>'text','text'=>'INTRODUCTION UNIQUE'],
            ['type'=>'image','ref'=>'new:1','width'=>320,'align'=>'center','caption'=>'Au milieu'],
            ['type'=>'text','text'=>'CONCLUSION UNIQUE'],
            ['type'=>'image','ref'=>'new:2','width'=>560,'align'=>'right','caption'=>'Dernière photo'],
        ];
        $this->post(route('admin.emails.store'), ['subject'=>'Test','headline'=>'Titre','audience'=>'all',
            'content_layout'=>json_encode($blocks),'images'=>[$this->photo(),$this->photo(),$this->photo()]])->assertRedirect();
        $email = ManagedEmail::sole();
        $this->assertSame(80, $email->metadata['content_blocks'][0]['width']);
        $this->assertSame("INTRODUCTION UNIQUE\n\nCONCLUSION UNIQUE", $email->body);
        $html=(new ManagedEmailMail($email))->render();
        $this->assertTrue(strpos($html,'alt="Portrait"') < strpos($html,'INTRODUCTION UNIQUE'));
        $this->assertTrue(strpos($html,'INTRODUCTION UNIQUE') < strpos($html,'alt="Au milieu"'));
        $this->assertTrue(strpos($html,'CONCLUSION UNIQUE') < strpos($html,'alt="Dernière photo"'));
        $this->assertStringContainsString('width="80"', $html);
        $this->get(route('admin.emails.show',$email))->assertOk()->assertSee('Composez votre message par blocs');
        $kept=$email->image_paths[1];
        $this->put(route('admin.emails.update',$email), ['subject'=>'Test','headline'=>'Titre',
            'content_layout'=>json_encode([
                ['type'=>'text','text'=>'Texte modifié <script>alert(1)</script>'],
                ['type'=>'image','ref'=>'existing:1','width'=>160,'align'=>'right','caption'=>'Photo conservée'],
            ])])->assertRedirect();
        $email->refresh();
        $this->assertSame([$kept],$email->image_paths);
        $this->assertSame(160,$email->metadata['content_blocks'][1]['width']);
        Storage::disk(config('filesystems.default'))->assertExists($kept);
        $this->assertStringNotContainsString('<script>alert(1)</script>',(new ManagedEmailMail($email))->render());
        $this->assertSame('pending',$email->status);
        Mail::assertNothingSent();
    }

    public function test_forged_paths_widths_and_missing_images_are_rejected(): void
    {
        $this->setupComposer();
        foreach ([
            ['type'=>'image','ref'=>'existing:0','width'=>80],
            ['type'=>'image','ref'=>'new:0','width'=>9000],
            ['type'=>'image','ref'=>'../../private.png','width'=>80],
        ] as $image) {
            $this->postJson(route('admin.emails.store'), ['subject'=>'Test','headline'=>'Titre','audience'=>'all',
                'content_layout'=>json_encode([['type'=>'text','text'=>'Bonjour'],$image])])->assertUnprocessable();
        }
        $this->postJson(route('admin.emails.store'), ['subject'=>'Test','headline'=>'Titre','audience'=>'all','content_layout'=>'not json'])->assertUnprocessable();
        $this->assertDatabaseCount('managed_emails',0);
        Mail::assertNothingSent();
    }
}
