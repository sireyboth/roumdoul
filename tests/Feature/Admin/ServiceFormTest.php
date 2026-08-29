<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_service_page_renders(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateService::class)->assertSuccessful();
    }

    public function test_edit_service_page_renders_with_existing_repeater_data(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create([
            'how_to_use_steps' => ['Step one', 'Step two'],
            'faqs' => [
                ['question' => 'Is this real?', 'answer' => 'Yes.'],
            ],
        ]);

        $component = Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
            ->assertSuccessful();

        $this->assertSame(
            ['Step one', 'Step two'],
            array_values($component->instance()->form->getState()['how_to_use_steps']),
        );
    }
}
