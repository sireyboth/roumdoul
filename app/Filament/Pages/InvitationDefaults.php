<?php

namespace App\Filament\Pages;

use App\Filament\Resources\InvitationTemplates\Schemas\InvitationTemplateForm;
use App\Models\InvitationTemplate;
use App\Support\InvitationDefaults as Defaults;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Admin › Catalog › Invitation defaults: the values a new invitation template starts with (see
 * App\Support\InvitationDefaults). Changing them never touches templates or invitations already made.
 *
 * @property-read Schema $form
 */
class InvitationDefaults extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Invitation defaults';

    protected static ?string $title = 'Invitation defaults';

    protected ?string $subheading = 'What a new invitation template starts with. You can still change everything in each template. Saving here never changes templates already made.';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Defaults::all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Defaults')->tabs([
                    $this->planTab(),
                    $this->shopTab(),
                    $this->presetsTab(),
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save defaults')->submit('save'),
                        Action::make('reset')
                            ->label('Back to built-in defaults')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->modalDescription('Replaces every value on this page with the built-in starting values.')
                            ->action(fn () => $this->resetToBuiltIn()),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        Defaults::save($this->form->getState());

        Notification::make()->title('Defaults saved')->success()->send();
    }

    public function resetToBuiltIn(): void
    {
        Defaults::save(Defaults::DEFAULTS);
        $this->form->fill(Defaults::DEFAULTS);

        Notification::make()->title('Back to the built-in defaults')->success()->send();
    }

    protected function planTab(): Tab
    {
        return Tab::make('Plans & add-ons')
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->components([
                Section::make('Starting plans')
                    ->description('A new template starts with these plans. Only for invitations — selling a normal service never uses them.')
                    ->components([
                        Repeater::make('plans')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Plan name'),
                                TableColumn::make('Price ($)'),
                                TableColumn::make('Badge'),
                                TableColumn::make('Max guests'),
                                TableColumn::make('Max photos'),
                                TableColumn::make('Online (months)'),
                            ])
                            ->components([
                                TextInput::make('label')->required(),
                                TextInput::make('price')->numeric()->minValue(0)->required(),
                                TextInput::make('badge')->maxLength(40),
                                TextInput::make('max_recipients')->numeric()->integer()->minValue(1),
                                TextInput::make('max_gallery_photos')->numeric()->integer()->minValue(1)->maxValue(100),
                                TextInput::make('retention_months')->numeric()->integer()->minValue(1),
                            ])
                            ->addActionLabel('Add plan')
                            ->reorderableWithButtons()
                            ->minItems(1),
                    ]),
                Section::make('Add-ons')
                    ->description('Pre-filled on every new invitation plan. Blank = not sold.')
                    ->columns(4)
                    ->components([
                        TextInput::make('extra_guest_price')->label('Per extra guest')->numeric()->minValue(0)->prefix('$'),
                        TextInput::make('extra_photo_price')->label('Per extra photo')->numeric()->minValue(0)->prefix('$'),
                        TextInput::make('extend_price')->label('Price to extend')->numeric()->minValue(0)->prefix('$'),
                        TextInput::make('extend_months')->label('Extend by (months)')->numeric()->integer()->minValue(1)->placeholder('6'),
                        Repeater::make('feature_prices')
                            ->label('Unlock one extra on its own')
                            ->table([
                                TableColumn::make('Extra'),
                                TableColumn::make('Price')->width('12rem'),
                            ])
                            ->components([
                                Select::make('feature')
                                    ->options(collect(InvitationTemplate::markableFieldOptions())->all())
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                TextInput::make('price')->numeric()->minValue(0.01)->prefix('$')->required(),
                            ])
                            ->addActionLabel('Add an extra')
                            ->columnSpanFull(),
                    ]),
                Section::make('Dropdown suggestions')
                    ->description('Values offered under the plan inputs. You can always type another value. Press Enter after each one.')
                    ->columns(2)
                    ->components([
                        TagsInput::make('suggest_labels')->label('Plan names'),
                        TagsInput::make('suggest_prices')->label('Prices ($)'),
                        TagsInput::make('suggest_recipients')->label('Max guests'),
                        TagsInput::make('suggest_photos')->label('Max photos'),
                        TagsInput::make('suggest_months')->label('Online (months)'),
                    ]),
            ]);
    }

    protected function shopTab(): Tab
    {
        return Tab::make('Shop page')
            ->icon(Heroicon::OutlinedShoppingBag)
            ->columns(2)
            ->components([
                Section::make('Texts')
                    ->columnSpanFull()
                    ->columns(3)
                    ->components([
                        TextInput::make('short_description')->label('Short description')->maxLength(255)->columnSpan(2),
                        TextInput::make('sort_order')->label('Sort order')->numeric()->integer()->helperText('Lower numbers appear first.'),
                        Textarea::make('description')
                            ->label('Full description')
                            ->helperText('Blank line = new paragraph, **bold**, start a line with "- " for a bullet list.')
                            ->rows(14)
                            ->columnSpanFull(),
                    ]),
                Section::make('How to use')
                    ->columnSpan(1)
                    ->components([
                        Repeater::make('how_to_use_steps')
                            ->hiddenLabel()
                            ->simple(TextInput::make('step')->required())
                            ->addActionLabel('Add step')
                            ->reorderableWithButtons(),
                    ]),
                Section::make('FAQ')
                    ->columnSpan(1)
                    ->components([
                        Repeater::make('faqs')
                            ->hiddenLabel()
                            ->components([
                                TextInput::make('question')->required(),
                                Textarea::make('answer')->required()->rows(2),
                            ])
                            ->itemLabel(fn (array $state) => $state['question'] ?? 'New question')
                            ->addActionLabel('Add question')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->collapsed(),
                    ]),
            ]);
    }

    protected function presetsTab(): Tab
    {
        $groups = collect(InvitationTemplateForm::GROUPS)
            ->filter(fn ($group, $key) => isset(InvitationTemplate::FIELD_SECTIONS[$key]))
            ->map(fn ($group) => $group['label'])
            ->all();

        return Tab::make('Fields by category')
            ->icon(Heroicon::OutlinedListBullet)
            ->components([
                Repeater::make('category_presets')
                    ->label('When the category contains…')
                    ->helperText('Typing this word in a template\'s Category (Basics tab) ticks these fields for you. Nothing is ever unticked.')
                    ->components([
                        TextInput::make('category')->label('Category word')->placeholder('wedding')->required(),
                        CheckboxList::make('sections')->label('Tick whole groups')->options($groups)->columns(3),
                        CheckboxList::make('fields')
                            ->label('Also tick these fields')
                            ->options(collect(InvitationTemplate::FIELD_CATALOG)->map(fn ($f) => $f['label'])->all())
                            ->searchable()
                            ->columns(4)
                            ->bulkToggleable(),
                    ])
                    ->itemLabel(fn (array $state) => filled($state['category'] ?? null) ? 'Category: '.$state['category'] : 'New preset')
                    ->addActionLabel('Add category')
                    ->collapsible(),
            ]);
    }
}
