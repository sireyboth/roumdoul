<?php

namespace App\Filament\Resources\InvitationTemplates\Pages;

use App\Filament\Resources\InvitationTemplates\InvitationTemplateActions;
use App\Filament\Resources\InvitationTemplates\InvitationTemplateResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * The only full page left for templates: a short summary plus the «Invitations using this template»
 * table (a relation manager needs a record page). Editing still happens in the same modal as the list.
 */
class ViewInvitationTemplate extends ViewRecord
{
    protected static string $resource = InvitationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            InvitationTemplateActions::edit(),
            InvitationTemplateActions::setUpPricing(),
            InvitationTemplateActions::delete(),
        ];
    }
}
