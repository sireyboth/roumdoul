<?php

namespace App\Filament\Resources\InvitationTemplates;

use App\Filament\Resources\InvitationTemplates\Pages\ListInvitationTemplates;
use App\Filament\Resources\InvitationTemplates\Pages\ViewInvitationTemplate;
use App\Filament\Resources\InvitationTemplates\RelationManagers\InvitationsRelationManager;
use App\Filament\Resources\InvitationTemplates\Schemas\InvitationTemplateForm;
use App\Filament\Resources\InvitationTemplates\Tables\InvitationTemplatesTable;
use App\Models\InvitationTemplate;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InvitationTemplateResource extends Resource
{
    protected static ?string $model = InvitationTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Invitation Templates';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return InvitationTemplateForm::configure($schema);
    }

    /** The summary at the top of the template's page (above the invitations table). */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(6)
                    ->components([
                        ImageEntry::make('thumbnail')
                            ->hiddenLabel()
                            ->disk('s3')
                            ->square()
                            ->imageSize(96)
                            ->placeholder('No thumbnail'),
                        TextEntry::make('name')
                            ->label('Product name')
                            ->weight('bold')
                            ->helperText(fn (InvitationTemplate $record) => $record->slug),
                        TextEntry::make('category')
                            ->badge(),
                        TextEntry::make('service.name_en')
                            ->label('Sold as')
                            ->placeholder('Not in the shop'),
                        TextEntry::make('fields_count')
                            ->label('Fields')
                            ->state(fn (InvitationTemplate $record) => count($record->fields ?? []))
                            ->badge(),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return InvitationTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            InvitationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            // create + edit open in modals; the view page holds the «Invitations using this template» table
            'index' => ListInvitationTemplates::route('/'),
            'view' => ViewInvitationTemplate::route('/{record}'),
        ];
    }
}
