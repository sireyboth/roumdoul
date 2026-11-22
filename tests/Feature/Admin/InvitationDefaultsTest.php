<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\InvitationDefaults as InvitationDefaultsPage;
use App\Filament\Resources\InvitationTemplates\Pages\ListInvitationTemplates;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\InvitationTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Support\InvitationDefaults;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationDefaultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::forgetMemo();
        $this->actingAs(User::factory()->create());
    }

    private function createModal()
    {
        return Livewire::test(ListInvitationTemplates::class)->mountAction(CreateAction::class);
    }

    private function first(array $items): array
    {
        return array_values($items)[0];
    }

    public function test_a_new_template_starts_with_the_default_plan_and_addons(): void
    {
        $data = $this->createModal()->get('mountedActions.0.data');
        $plan = $this->first($data['plans']);

        $this->assertSame('Basic', $plan['label']);
        $this->assertSame(100, $plan['max_recipients']);
        $this->assertSame(6, $plan['max_gallery_photos']);
        $this->assertSame(6, $plan['retention_months']);
        $this->assertEquals(0.1, $plan['extra_guest_price']);
        $this->assertEquals(0.25, $plan['extra_photo_price']);
        $this->assertEquals(6, $plan['extend_price']);
        $this->assertEqualsCanonicalizing(['qr_code', 'map_url', 'event_schedule'], array_column($plan['feature_prices'], 'feature'));
    }

    public function test_a_new_template_starts_with_the_default_shop_texts_order_steps_and_faq(): void
    {
        $shop = $this->createModal()->get('mountedActions.0.data.shop');

        $this->assertSame(InvitationDefaults::DEFAULTS['short_description'], $shop['short_description']);
        $this->assertSame(InvitationDefaults::DEFAULTS['description'], $shop['description']);
        $this->assertEquals(2, $shop['sort_order']);
        $this->assertCount(count(InvitationDefaults::DEFAULTS['how_to_use_steps']), $shop['how_to_use_steps']);
        $this->assertCount(count(InvitationDefaults::DEFAULTS['faqs']), $shop['faqs']);
    }

    public function test_saved_defaults_are_used_by_the_next_new_template(): void
    {
        InvitationDefaults::save([...InvitationDefaults::DEFAULTS,
            'plans' => [['label' => 'Gold', 'price' => 12, 'max_recipients' => 300, 'max_gallery_photos' => 20, 'retention_months' => 12]],
            'extra_guest_price' => 0.2,
            'sort_order' => 7,
        ]);

        $data = $this->createModal()->get('mountedActions.0.data');
        $plan = $this->first($data['plans']);

        $this->assertSame('Gold', $plan['label']);
        $this->assertSame(300, $plan['max_recipients']);
        $this->assertEquals(0.2, $plan['extra_guest_price']);
        $this->assertEquals(7, $data['shop']['sort_order']);
    }

    public function test_the_defaults_page_saves(): void
    {
        Livewire::test(InvitationDefaultsPage::class)
            ->assertSuccessful()
            ->set('data.extend_price', 9)
            ->set('data.short_description', 'Hello')
            ->call('save')
            ->assertHasNoErrors();

        Setting::forgetMemo();
        $this->assertEquals(9, InvitationDefaults::get('extend_price'));
        $this->assertSame('Hello', InvitationDefaults::get('short_description'));
    }

    public function test_a_wedding_category_ticks_the_wedding_fields(): void
    {
        $data = $this->createModal()
            ->set('mountedActions.0.data.category', 'wedding')
            ->get('mountedActions.0.data');

        $this->assertEqualsCanonicalizing(InvitationTemplate::FIELD_SECTIONS['essentials'], $data['fields_essentials']);
        $this->assertEqualsCanonicalizing(InvitationTemplate::FIELD_SECTIONS['premium'], $data['fields_premium']);
        $this->assertEqualsCanonicalizing(InvitationTemplate::FIELD_SECTIONS['wedding'], $data['fields_wedding']);
        $this->assertSame(['dress_code'], $data['fields_wording']);
        $this->assertContains('qr_code', $data['premium_fields']);
    }

    public function test_a_plain_service_plan_gets_no_invitation_defaults(): void
    {
        $data = Livewire::test(ListServices::class)
            ->mountAction(CreateAction::class)
            ->get('mountedActions.0.data');

        $this->assertEmpty($data['plans'] ?? []);

        $this->assertSame(0, (int) ($data['sort_order'] ?? 0));
        $this->assertEmpty($data['how_to_use_steps'] ?? []);
        $this->assertEmpty($data['short_description'] ?? null);
    }

    public function test_the_thumbnail_becomes_the_cover_when_no_cover_is_picked(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('invitation-templates/thumb.png', UploadedFile::fake()->image('thumb.png')->getContent());

        Livewire::test(ListInvitationTemplates::class)
            ->callAction(CreateAction::class, [
                'name' => 'Rose Garden',
                'slug' => 'rose-garden',
                'category' => 'birthday',
                'view' => 'invitations.templates.date-asking',
                'thumbnail' => ['invitation-templates/thumb.png'],
                'fields_essentials' => ['headline'],
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        $service = InvitationTemplate::where('slug', 'rose-garden')->firstOrFail()->service;
        $this->assertNotNull($service->image_path);
        $this->assertStringStartsWith('services/', $service->image_path);
        Storage::disk('s3')->assertExists($service->image_path);
    }
}
