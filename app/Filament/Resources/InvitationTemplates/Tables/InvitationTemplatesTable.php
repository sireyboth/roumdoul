<?php

namespace App\Filament\Resources\InvitationTemplates\Tables;

use App\Models\InvitationTemplate;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class InvitationTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('')
                    ->disk('s3')
                    ->square(),
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->slug),
                TextColumn::make('category')
                    ->badge(),
                TextColumn::make('service.name_en')
                    ->label('Sold as')
                    ->placeholder('— not linked to a product —'),
                TextColumn::make('fields_count')
                    ->label('Fields')
                    ->state(fn ($record) => (string) count($record->fields ?? []))
                    ->badge(),
                IconColumn::make('is_premium')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
                TernaryFilter::make('is_premium')
                    ->label('Premium'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(fn (DeleteAction $action, InvitationTemplate $record) => static::cancelIfInUse($action, collect([$record]))),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(fn (DeleteBulkAction $action, Collection $records) => static::cancelIfInUse($action, $records)),
                ]),
            ]);
    }

    /**
     * Templates still used by live (non-trashed) invitations can't be deleted — the FK is
     * restrictOnDelete so customers' links don't break. Explain that instead of letting the
     * delete hit the DB and surface as a generic "Error while loading page".
     */
    public static function cancelIfInUse(Action $action, Collection $templates): void
    {
        $inUse = $templates
            ->map(fn (InvitationTemplate $t) => [$t->name, $t->invitations()->count()])
            ->filter(fn (array $row) => $row[1] > 0);

        if ($inUse->isEmpty()) {
            return;
        }

        Notification::make()
            ->danger()
            ->title('Template is still in use')
            ->body(new HtmlString(
                $inUse->map(fn (array $row) => e($row[0])." — used by {$row[1]} invitation(s)")->implode('<br>')
                .'<br>Turn off "Active" instead to stop offering it, or delete those invitations first '
                .'from the "Invitations using this template" table on the Edit page.'
            ))
            ->persistent()
            ->send();

        $action->cancel();
    }
}
