<?php

namespace Tests\Feature;

use App\Livewire\Components\ShopCatalog;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeShopSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_shows_the_shop_section_with_every_product(): void
    {
        $service = Service::factory()->create(['name_en' => 'Gemini Pro', 'is_active' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="shop"', false)
            ->assertSee('Gemini Pro');
    }

    public function test_the_shop_section_filters_by_category_and_search_in_place(): void
    {
        $wedding = Category::factory()->create(['slug' => 'wedding']);
        $other = Category::factory()->create(['slug' => 'other']);
        Service::factory()->create(['name_en' => 'Rose Wedding', 'category_id' => $wedding->id, 'is_active' => true]);
        Service::factory()->create(['name_en' => 'Canva Pro', 'category_id' => $other->id, 'is_active' => true]);

        Livewire::test(ShopCatalog::class)
            ->assertSee('Rose Wedding')->assertSee('Canva Pro')
            ->call('setCategory', 'wedding')
            ->assertSee('Rose Wedding')->assertDontSee('Canva Pro')
            ->call('clearFilters')
            ->set('search', 'Canva')
            ->assertSee('Canva Pro')->assertDontSee('Rose Wedding');
    }

    public function test_the_shop_page_and_category_pages_still_work(): void
    {
        $category = Category::factory()->create(['slug' => 'apps']);
        Service::factory()->create(['name_en' => 'Netflix', 'category_id' => $category->id, 'is_active' => true]);

        $this->get('/shop')->assertOk()->assertSee('Netflix');
        $this->get('/shop/apps')->assertOk()->assertSee('Netflix');
    }
}
