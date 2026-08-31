<?php

namespace App\Filament\Resources\InvitationTemplates\Pages;

use App\Filament\Resources\InvitationTemplates\InvitationTemplateResource;
use App\Models\Category;
use App\Models\InvitationTemplate;
use App\Models\Service;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateInvitationTemplate extends CreateRecord
{
    protected static string $resource = InvitationTemplateResource::class;

    /**
     * The form has no "Services" step — creating a template creates its underlying
     * Service + ServicePlans automatically, so admin never has to visit that screen.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $data = InvitationTemplate::mergeFieldSections($data);

            $plans = $data['plans'] ?? [];
            unset($data['plans']);

            $category = Category::firstOrCreate(
                ['slug' => 'digital-invitations'],
                ['name_en' => 'Digital Invitations', 'name_km' => 'កម្មវត្ថុអញ្ជើញឌីជីថល', 'icon' => 'sparkles']
            );

            $service = Service::create([
                'category_id' => $category->id,
                'name_en' => $data['name'],
                'name_km' => $data['name'],
                'slug' => Str::slug($data['name']).'-'.Str::random(6),
                'short_description' => $data['name'],
                'description' => $data['name'],
                'base_price' => $plans[0]['price'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach ($plans as $index => $plan) {
                $service->plans()->create([
                    'label' => $plan['label'],
                    'price' => $plan['price'],
                    'max_recipients' => $plan['max_recipients'] ?? null,
                    'retention_months' => $plan['retention_months'] ?? null,
                    'features' => $plan['features'] ?? [],
                    'sort_order' => $index,
                ]);
            }

            $data['service_id'] = $service->id;

            return static::getModel()::create($data);
        });
    }
}
