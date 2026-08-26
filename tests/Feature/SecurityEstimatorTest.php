<?php

namespace Tests\Feature;

use App\Livewire\Components\SecurityEstimator;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityEstimatorTest extends TestCase
{
    public function test_it_walks_through_the_three_steps(): void
    {
        Livewire::test(SecurityEstimator::class)
            ->assertSet('step', 1)
            ->call('selectPropertyType', 'residential')
            ->assertSet('step', 2)
            ->assertSet('propertyType', 'residential')
            ->call('toggleService', 'guards')
            ->call('toggleService', 'cctv')
            ->assertSet('services', ['guards', 'cctv'])
            ->call('goToCoverageStep')
            ->assertSet('step', 3)
            ->call('selectCoverageHours', '247')
            ->assertSet('coverageHours', '247');
    }

    public function test_it_wont_advance_past_services_step_with_nothing_selected(): void
    {
        Livewire::test(SecurityEstimator::class)
            ->call('selectPropertyType', 'commercial')
            ->call('goToCoverageStep')
            ->assertSet('step', 2);
    }

    public function test_it_redirects_to_contact_with_prefilled_query_params(): void
    {
        Livewire::test(SecurityEstimator::class)
            ->call('selectPropertyType', 'event')
            ->call('toggleService', 'guards')
            ->call('goToCoverageStep')
            ->call('selectCoverageHours', 'event')
            ->call('getRecommendation')
            ->assertRedirectContains('/contact?')
            ->assertRedirectContains('service=events');
    }
}
