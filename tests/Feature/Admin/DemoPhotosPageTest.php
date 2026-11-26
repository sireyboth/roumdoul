<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\DemoPhotos as DemoPhotosPage;
use App\Models\Setting;
use App\Models\User;
use App\Support\DemoPhotos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DemoPhotosPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::forgetMemo();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_the_page_opens_with_a_box_for_every_role(): void
    {
        $this->get(DemoPhotosPage::getUrl())->assertOk()->assertSee('Demo photos')->assertSee('Story 5 · forever')->assertSee('Gallery 12');
    }

    public function test_saving_keeps_the_role_map_and_deletes_a_replaced_photo_from_the_bucket(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('demo/couple/old-cover.webp', 'x');
        Storage::disk('s3')->put('demo/couple/g1.webp', 'x');
        DemoPhotos::save(['photos' => ['cover' => 'demo/couple/old-cover.webp', 'gallery_1' => 'demo/couple/g1.webp']]);

        Livewire::test(DemoPhotosPage::class)
            ->assertSet('data.photos.gallery_1', fn ($v) => in_array('demo/couple/g1.webp', (array) $v, true))
            ->set('data.photos.cover', null)
            ->set('data.captions.gallery_1', 'ថ្ងៃដ៏ស្រស់ស្អាត')
            ->call('save')
            ->assertHasNoErrors();

        $saved = DemoPhotos::all();
        $this->assertNull($saved['photos']['cover']);
        $this->assertSame('demo/couple/g1.webp', $saved['photos']['gallery_1']);
        $this->assertSame('ថ្ងៃដ៏ស្រស់ស្អាត', DemoPhotos::captions());
        Storage::disk('s3')->assertMissing('demo/couple/old-cover.webp');
    }
}
