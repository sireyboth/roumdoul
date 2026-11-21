<?php

namespace App\Filament\Resources\InvitationTemplates\RelationManagers;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\InvitationTemplates\Schemas\InvitationTemplateForm;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
use App\Services\InvitationAddons;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

/**
 * The customer invitations built on this template — the ones that block deleting it.
 * Deleting here soft-deletes the invitation (its guest links stop working); once none
 * are left, the template itself can be deleted and purges the trashed ones with it.
 *
 * «Edit limits» lets the owner give one customer more guests / photos / extras / time by hand
 * (each invitation keeps its own copy of its plan's limits).
 */
class InvitationsRelationManager extends RelationManager
{
    protected static string $relationship = 'invitations';

    protected static ?string $title = 'Invitations using this template';

    /** It lives on the template's view page, which would make it read-only by default. */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return self::limitsForm($schema);
    }

    /** The per-invitation limits form (wide, two side-by-side sections). */
    public static function limitsForm(Schema $schema): Schema
    {
        return $schema
            ->columns(5)
            ->components([
                Section::make('Limits')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->description('Raise these to give the customer more for free. Changing the plan in the shop does not touch invitations already sold.')
                    ->columnSpan(2)
                    ->components([
                        TextInput::make('max_recipients')
                            ->label('Max guests (links)')
                            ->helperText(fn (?Invitation $record) => $record
                                ? 'How many personal guest links they can make. Already made: '.$record->recipients()->count().'.'
                                : 'How many personal guest links they can make.')
                            ->numeric()
                            ->integer()
                            ->minValue(fn (?Invitation $record) => max(1, (int) $record?->recipients()->count()))
                            ->required(),
                        TextInput::make('max_gallery_photos')
                            ->label('Max gallery photos')
                            ->helperText(fn (?Invitation $record) => 'Blank = the plan\'s limit'
                                .($record ? ' (now '.$record->maxGalleryPhotos().')' : '').'. Up to '.InvitationAddons::MAX_GALLERY.'.')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(InvitationAddons::MAX_GALLERY),
                        DateTimePicker::make('expires_at')
                            ->label('Online until')
                            ->helperText('Blank = never expires. Moving it later also re-arms the «about to expire» email.')
                            ->native(false),
                    ]),
                Section::make('Unlocked extras')
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->description('Premium fields this invitation can use. Ticked = unlocked for this customer only.')
                    ->columnSpan(3)
                    ->components([
                        CheckboxList::make('features')
                            ->hiddenLabel()
                            ->options(collect(InvitationTemplate::premiumFieldOptions())->map(fn ($label, string $key) => InvitationTemplateForm::fieldOptionLabel($key, tag: false))->all())
                            ->allowHtml()
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->columns(2),
                    ]),
            ]);
    }

    /** Make the edit action wide, show the effective extras, and re-arm the expiry reminder when extended. */
    public static function editLimitsAction(): EditAction
    {
        return EditAction::make()
            ->label('Edit limits')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->modalHeading(fn (Invitation $record) => 'Limits for invitation #'.$record->id.($record->customer ? ' — '.$record->customer->name : ''))
            ->modalWidth(Width::FiveExtraLarge)
            // invitations made before they kept their own list fall back to the plan's — show that list
            ->mutateRecordDataUsing(function (array $data, Invitation $record): array {
                $data['features'] = $record->features ?? $record->plan?->features ?? [];

                return $data;
            })
            ->mutateDataUsing(function (array $data, Invitation $record): array {
                $data['features'] = array_values($data['features'] ?? []);

                $newExpiry = $data['expires_at'] ?? null;
                if ($newExpiry !== null && ! $record->expires_at?->equalTo(Carbon::parse($newExpiry))) {
                    $data['expiry_reminded_at'] = null; // a new expiry date earns a new reminder
                }

                return $data;
            });
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('slug')
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'order', 'plan'])->withCount('recipients'))
            ->columns([
                TextColumn::make('slug')
                    ->label('Link')
                    ->description(fn (Invitation $record) => '#'.$record->id)
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->description(fn (Invitation $record) => $record->customer?->email)
                    ->url(fn (Invitation $record) => $record->customer_id
                        ? CustomerResource::getUrl('index', ['tableAction' => 'edit', 'tableActionRecord' => $record->customer_id])
                        : null)
                    ->placeholder('—'),
                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->url(fn (Invitation $record) => $record->order_id
                        ? OrderResource::getUrl('index', ['tableAction' => 'view', 'tableActionRecord' => $record->order_id])
                        : null)
                    ->placeholder('—'),
                TextColumn::make('order.status')
                    ->label('Order status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => OrderForm::STATUSES[$state] ?? $state)
                    ->color(fn (?string $state) => OrderForm::statusColor($state))
                    ->placeholder('—'),
                TextColumn::make('plan.label')
                    ->label('Plan')
                    ->placeholder('—'),
                TextColumn::make('recipients_count')
                    ->label('Guests')
                    ->state(fn (Invitation $record) => $record->recipients_count.' / '.$record->max_recipients)
                    ->badge()
                    ->color(fn (Invitation $record) => $record->recipients_count >= $record->max_recipients ? 'warning' : 'gray'),
                TextColumn::make('max_gallery_photos')
                    ->label('Photos')
                    ->state(fn (Invitation $record) => $record->maxGalleryPhotos())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('features')
                    ->label('Extras')
                    ->state(fn (Invitation $record) => count($record->features ?? $record->plan?->features ?? []))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('expires_at')
                    ->label('Online until')
                    ->dateTime()
                    ->color(fn (Invitation $record) => $record->isExpired() ? 'danger' : null)
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon(Heroicon::OutlinedEnvelope)
            ->emptyStateHeading('No invitations yet')
            ->emptyStateDescription('When a customer buys this template and the order is marked paid, their invitation appears here.')
            ->headerActions([])
            ->recordAction('edit')
            ->recordActions([
                Action::make('openLink')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->visible(fn (Invitation $record) => $record->recipients()->exists())
                    ->url(fn (Invitation $record) => route('invitation.show', $record->recipients()->oldest('id')->first()))
                    ->openUrlInNewTab(),
                self::editLimitsAction(),
                DeleteAction::make()
                    ->modalDescription('All guest links for this invitation will stop working. This can\'t be undone from the admin panel.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription('All guest links for these invitations will stop working.'),
                ]),
            ]);
    }
}
