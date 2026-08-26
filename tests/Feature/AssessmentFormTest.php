<?php

namespace Tests\Feature;

use App\Livewire\Components\AssessmentForm;
use App\Models\SiteAssessmentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_valid_input(): void
    {
        Livewire::test(AssessmentForm::class)
            ->set('full_name', '')
            ->set('phone', '')
            ->set('service_needed', 'not-a-real-option')
            ->set('location', '')
            ->call('submit')
            ->assertHasErrors(['full_name', 'phone', 'service_needed', 'location']);

        $this->assertSame(0, SiteAssessmentRequest::count());
    }

    public function test_it_stores_a_valid_submission(): void
    {
        Livewire::test(AssessmentForm::class)
            ->set('full_name', 'Sokha Chan')
            ->set('phone', '012 345 678')
            ->set('service_needed', 'commercial')
            ->set('location', 'Phnom Penh')
            ->set('message', 'Need coverage for a 5-story office building.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('site_assessment_requests', [
            'full_name' => 'Sokha Chan',
            'phone' => '012 345 678',
            'service_needed' => 'commercial',
            'location' => 'Phnom Penh',
        ]);
    }
}
