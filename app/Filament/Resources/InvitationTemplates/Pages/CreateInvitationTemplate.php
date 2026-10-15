<?php

namespace App\Filament\Resources\InvitationTemplates\Pages;

use App\Filament\Resources\InvitationTemplates\InvitationTemplateResource;
use App\Models\InvitationTemplate;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

            $service = InvitationTemplate::createServiceFor($data['name'], $data['is_active'] ?? true, $plans);

            $data['service_id'] = $service->id;

            return static::getModel()::create($data);
        });
    }
}
