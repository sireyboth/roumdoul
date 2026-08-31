<?php

namespace App\Filament\Resources\InvitationTemplates\Schemas;

use App\Models\InvitationTemplate;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InvitationTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->columnSpanFull()
                    ->components([
                        self::templateSection(),
                        self::pricingPlansSection(),
                    ]),

                self::fieldsSection(),
            ]);
    }

    protected static function templateSection(): Section
    {
        return Section::make('Template')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Product name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in the demo URL: /templates/{slug}/demo'),
                        TextInput::make('category')
                            ->required()
                            ->helperText('A grouping label, e.g. "wedding", "date-asking", "breakup".'),
                        Select::make('view')
                            ->label('Design (Blade view)')
                            ->options(self::availableViews())
                            ->required()
                            ->helperText('The Blade file that renders this design — a developer adds new options here by creating the file under resources/views/invitations/templates/.'),
                        FileUpload::make('thumbnail')
                            ->image()
                            ->disk('s3')
                            ->directory('invitation-templates')
                            ->helperText('Optional preview thumbnail for the template picker.'),
                        Toggle::make('is_premium')
                            ->label('Premium')
                            ->helperText('Only shown to customers with a plan that unlocks it.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Off hides it from the template picker and demo page.'),
                    ]);
    }

    protected static function pricingPlansSection(): Section
    {
        return Section::make('Pricing plans')
                    ->description('What customers actually buy. Each plan sets how many recipient links they get, how long it stays active, and which extra features it unlocks.')
                    ->visible(fn (string $operation) => $operation === 'create')
                    ->components([
                        Repeater::make('plans')
                            ->label('')
                            ->addActionLabel('Add plan')
                            ->reorderableWithButtons()
                            ->itemLabel(fn (array $state) => $state['label'] ?? 'New plan')
                            ->columns(2)
                            ->components([
                                TextInput::make('label')
                                    ->required()
                                    ->placeholder('e.g. "Basic — 10 recipients, 3 months"'),
                                TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$'),
                                TextInput::make('max_recipients')
                                    ->label('Max recipients')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),
                                TextInput::make('retention_months')
                                    ->label('Retention (months)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText('Leave blank for no expiry.'),
                                CheckboxList::make('features')
                                    ->label('Unlocked fields (premium extras)')
                                    ->helperText('The essentials (name, headline, message, date, venue name, music, photo, color) are always included on every plan. Tick which of these extras this plan also unlocks — a template can still include an extra field even if no plan unlocks it yet.')
                                    ->options(InvitationTemplate::premiumFieldOptions())
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->required(),
                    ]);
    }

    protected static function fieldsSection(): Section
    {
        return Section::make('Fields this design uses')
                    ->description('Which of the standard fields this template asks the customer to fill in, grouped by which event type they apply to. Adding a genuinely new field means adding it to InvitationTemplate::FIELD_CATALOG (and FIELD_SECTIONS) in code first.')
                    ->columnSpanFull()
                    ->components([
                        CheckboxList::make('fields_universal')
                            ->label('Universal — works for any event')
                            ->options(InvitationTemplate::sectionFieldOptions('universal'))
                            ->columns(3)
                            ->columnSpanFull(),
                        CheckboxList::make('fields_wedding')
                            ->label('Wedding-specific')
                            ->options(InvitationTemplate::sectionFieldOptions('wedding'))
                            ->columns(3)
                            ->columnSpanFull(),
                        CheckboxList::make('fields_birthday')
                            ->label('Birthday-specific')
                            ->options(InvitationTemplate::sectionFieldOptions('birthday'))
                            ->columns(3)
                            ->columnSpanFull(),
                    ]);
    }

    /**
     * Every Blade file under resources/views/invitations/templates/, as dot-notation
     * view names a developer has already built and is ready to be assigned to a template.
     */
    protected static function availableViews(): array
    {
        $path = resource_path('views/invitations/templates');

        if (! File::isDirectory($path)) {
            return [];
        }

        return collect(File::files($path))
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), '.blade.php'))
            ->mapWithKeys(function ($file) {
                $name = Str::before($file->getFilename(), '.blade.php');

                return ["invitations.templates.{$name}" => $name];
            })
            ->sort()
            ->toArray();
    }
}
