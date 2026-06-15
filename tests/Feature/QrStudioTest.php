<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Link;
use App\Models\QrCode;
use App\Models\QrTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class QrStudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_static_qr_is_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/qr', [
            'name' => 'Office WiFi',
            'type' => 'wifi',
            'content' => 'WIFI:T:WPA;S:Net;P:pass;;',
            'data' => json_encode(['ssid' => 'Net', 'password' => 'pass', 'encryption' => 'WPA']),
            'design' => json_encode(['dotsType' => 'dots', 'fg' => '#0f172a']),
        ])->assertRedirect(route('qr.index'));

        $this->assertDatabaseHas('qr_codes', [
            'user_id' => $user->id, 'type' => 'wifi', 'is_dynamic' => false, 'link_id' => null,
        ]);
    }

    public function test_dynamic_qr_creates_a_tracked_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/qr', [
            'name' => 'Promo',
            'type' => 'link',
            'is_dynamic' => '1',
            'content' => 'https://example.com/promo',
            'data' => json_encode(['url' => 'https://example.com/promo']),
            'design' => json_encode(['dotsType' => 'square']),
        ])->assertRedirect(route('qr.index'));

        $qr = QrCode::where('user_id', $user->id)->first();
        $this->assertTrue($qr->is_dynamic);
        $this->assertNotNull($qr->link_id);
        $this->assertDatabaseHas('links', [
            'id' => $qr->link_id, 'user_id' => $user->id, 'long_url' => 'https://example.com/promo',
        ]);
    }

    public function test_per_link_qr_binds_to_the_existing_link(): void
    {
        $user = User::factory()->create();
        $link = Link::create([
            'user_id' => $user->id,
            'domain_id' => Domain::where('is_default', true)->value('id'),
            'alias' => 'news',
            'long_url' => 'https://example.com/news',
            'type' => 'direct',
            'safety_status' => 'safe',
        ]);

        $this->actingAs($user)->get(route('links.qr', $link))->assertOk()->assertSee('tracked on the link');

        $before = $user->links()->count();
        $this->actingAs($user)->post('/qr', [
            'name' => 'News QR',
            'type' => 'link',
            'bound_link_id' => $link->id,
            'content' => 'http://localhost/news',
            'data' => json_encode(['url' => 'http://localhost/news']),
            'design' => json_encode(['dotsType' => 'rounded']),
        ])->assertRedirect(route('qr.index'));

        $this->assertDatabaseHas('qr_codes', ['user_id' => $user->id, 'link_id' => $link->id, 'is_dynamic' => true]);
        $this->assertSame($before, $user->links()->count()); // bound, so no new link minted
    }

    public function test_design_template_can_be_saved_and_deleted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/qr/templates', [
            'name' => 'Emerald brand',
            'design' => json_encode(['dotsType' => 'extra-rounded', 'gradient' => true]),
        ])->assertOk()->assertJsonPath('name', 'Emerald brand');

        $tpl = QrTemplate::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('extra-rounded', $tpl->design['dotsType']);

        // Owner-only delete.
        $intruder = User::factory()->create();
        $this->actingAs($intruder)->deleteJson("/qr/templates/{$tpl->id}")->assertForbidden();

        $this->actingAs($user)->deleteJson("/qr/templates/{$tpl->id}")->assertOk();
        $this->assertDatabaseMissing('qr_templates', ['id' => $tpl->id]);
    }

    public function test_bulk_csv_generates_dynamic_tracked_codes(): void
    {
        $user = User::factory()->create();
        $csv = "url,name\nhttps://example.com/a, Campaign A\nhttps://example.com/b, Campaign B\nnot-a-url, skip me";

        $this->actingAs($user)->post('/qr/bulk', [
            'csv' => UploadedFile::fake()->createWithContent('codes.csv', $csv),
        ])->assertRedirect(route('qr.index'));

        // Header + invalid row skipped; 2 valid rows created as dynamic codes + tracked links.
        $this->assertSame(2, $user->qrCodes()->count());
        $this->assertSame(2, $user->links()->count());
        $this->assertSame(2, $user->qrCodes()->where('is_dynamic', true)->count());
    }

    public function test_qr_studio_is_owner_only(): void
    {
        $owner = User::factory()->create();
        $qr = QrCode::create(['user_id' => $owner->id, 'type' => 'text', 'content' => 'hi', 'data' => [], 'design' => []]);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('qr.edit', $qr))->assertForbidden();
        $this->actingAs($intruder)->delete(route('qr.destroy', $qr))->assertForbidden();
    }
}
