<?php

namespace App\Livewire\Components;

use Livewire\Component;

class SecurityEstimator extends Component
{
    public int $step = 1;

    public string $propertyType = '';

    /** @var array<int, string> */
    public array $services = [];

    public string $coverageHours = '';

    public function propertyTypes(): array
    {
        return [
            'commercial' => 'អាជីវកម្ម',
            'residential' => 'លំនៅដ្ឋាន',
            'event' => 'ព្រឹត្តិការណ៍',
            'industrial' => 'រោងចក្រ/ឧស្សាហកម្ម',
        ];
    }

    public function serviceOptions(): array
    {
        return [
            'guards' => 'អ្នកយាមសន្តិសុខប្រចាំការ',
            'patrols' => 'ការល្បាត',
            'cctv' => 'ការត្រួតពិនិត្យ CCTV',
            'alarm' => 'ប្រព័ន្ធរោទិ៍សុវត្ថិភាព',
        ];
    }

    public function coverageOptions(): array
    {
        return [
            '12h' => '១២ម៉ោង',
            '247' => '២៤/៧',
            'event' => 'តាមព្រឹត្តិការណ៍',
        ];
    }

    public function selectPropertyType(string $type): void
    {
        $this->propertyType = $type;
        $this->step = 2;
    }

    public function toggleService(string $service): void
    {
        if (in_array($service, $this->services, true)) {
            $this->services = array_values(array_diff($this->services, [$service]));
        } else {
            $this->services[] = $service;
        }
    }

    public function goToServicesStep(): void
    {
        $this->step = 2;
    }

    public function goToCoverageStep(): void
    {
        if (empty($this->services)) {
            return;
        }

        $this->step = 3;
    }

    public function selectCoverageHours(string $hours): void
    {
        $this->coverageHours = $hours;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    // Maps the estimator's property type to the closest option already
    // offered by the contact form's service dropdown, since the two use
    // different vocabularies (property type vs. service package).
    private function mappedServiceNeeded(): string
    {
        return match ($this->propertyType) {
            'residential' => 'residential',
            'event' => 'events',
            default => 'commercial',
        };
    }

    public function getRecommendation()
    {
        $propertyLabel = $this->propertyTypes()[$this->propertyType] ?? '';
        $serviceLabels = array_map(fn ($key) => $this->serviceOptions()[$key] ?? $key, $this->services);
        $coverageLabel = $this->coverageOptions()[$this->coverageHours] ?? '';

        $message = "ខ្ញុំបានប្រើប្រាស់ឧបករណ៍ប៉ាន់ស្មានតម្រូវការសន្តិសុខលើគេហទំព័រ។\n"
            ."ប្រភេទទ្រព្យសម្បត្តិ៖ {$propertyLabel}\n"
            .'សេវាកម្មដែលត្រូវការ៖ '.implode(', ', $serviceLabels)."\n"
            ."ម៉ោងគ្របដណ្តប់៖ {$coverageLabel}";

        $query = http_build_query([
            'service' => $this->mappedServiceNeeded(),
            'message' => $message,
        ]);

        return $this->redirect('/contact?'.$query, navigate: true);
    }

    public function render()
    {
        return view('livewire.components.security-estimator');
    }
}
