<?php

namespace App\Livewire\Sections;

use Livewire\Component;

class PricingSection extends Component
{
    /**
     * @return array<int, array{
     *     key: string,
     *     name: string,
     *     subtitle: string,
     *     highlighted: bool,
     *     included: array<int, string>,
     *     excluded: array<int, string>,
     *     cta_href: string,
     * }>
     */
    public function packages(): array
    {
        return [
            $this->package(
                key: 'basic',
                name: 'កញ្ចប់មូលដ្ឋាន',
                subtitle: 'សម្រាប់អាជីវកម្មខ្នាតតូច',
                highlighted: false,
                included: [
                    'អ្នកយាមសន្តិសុខប្រចាំការ ១២ម៉ោង',
                    'កំណត់ត្រាល្បាតប្រចាំថ្ងៃ',
                    'ការគ្រប់គ្រងចំណុចចូល-ចេញជាមូលដ្ឋាន',
                    'ខ្សែទូរស័ព្ទបន្ទាន់ ២៤/៧',
                ],
                excluded: [
                    'អមដំណើរ VIP ដោយអាវុធ',
                    'ការភ្ជាប់ប្រព័ន្ធ CCTV ពីចម្ងាយ',
                ],
                serviceNeeded: 'commercial',
            ),
            $this->package(
                key: 'standard',
                name: 'កញ្ចប់ស្តង់ដារ',
                subtitle: 'សម្រាប់ហាងលក់រាយ និងសហគ្រាស',
                highlighted: true,
                included: [
                    'អ្នកយាមសន្តិសុខប្រចាំការ ២៤/៧ (វេនថ្ងៃ-យប់)',
                    'ការត្រួតពិនិត្យដោយអ្នកគ្រប់គ្រងទីតាំង',
                    'របាយការណ៍ និងកំណត់ត្រាហេតុការណ៍',
                    'ការដាក់ពង្រាយបម្រុងទុកករណីអាសន្ន',
                    'ការគ្រប់គ្រង និងពិនិត្យចំណុចចូល-ចេញ',
                ],
                excluded: [
                    'អមដំណើរ VIP ផ្ទាល់ខ្លួន',
                ],
                serviceNeeded: 'commercial',
            ),
            $this->package(
                key: 'premium',
                name: 'កញ្ចប់ពិសេស / VIP',
                subtitle: 'សម្រាប់ទ្រព្យសម្បត្តិ និងលំនៅដ្ឋានតម្លៃខ្ពស់',
                highlighted: false,
                included: [
                    'ក្រុមអ្នកយាមច្រើននាក់ ២៤/៧ + ការល្បាតចល័ត',
                    'ការត្រួតពិនិត្យ CCTV ជាមួយអ្នកឯកទេសផ្ទាល់',
                    'អមដំណើរ និងការពារ VIP ដោយសម្ងាត់',
                    'ការឆ្លើយតបករណីអាសន្នអាទិភាពខ្ពស់',
                    'ការវាយតម្លៃ និងសវនកម្មហានិភ័យសុវត្ថិភាពជាក់លាក់',
                ],
                excluded: [],
                serviceNeeded: 'executive',
            ),
        ];
    }

    /**
     * @param  array<int, string>  $included
     * @param  array<int, string>  $excluded
     * @return array{key: string, name: string, subtitle: string, highlighted: bool, included: array<int, string>, excluded: array<int, string>, cta_href: string}
     */
    private function package(
        string $key,
        string $name,
        string $subtitle,
        bool $highlighted,
        array $included,
        array $excluded,
        string $serviceNeeded,
    ): array {
        $query = http_build_query([
            'service' => $serviceNeeded,
            'message' => "ខ្ញុំចាប់អារម្មណ៍លើ {$name} ហើយចង់ស្នើសុំសម្រង់តម្លៃ។",
        ]);

        return [
            'key' => $key,
            'name' => $name,
            'subtitle' => $subtitle,
            'highlighted' => $highlighted,
            'included' => $included,
            'excluded' => $excluded,
            'cta_href' => "/contact?{$query}",
        ];
    }

    public function render()
    {
        return view('livewire.sections.pricing-section');
    }
}
