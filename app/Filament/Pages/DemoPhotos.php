<?php

namespace App\Filament\Pages;

use App\Support\DemoPhotos as Photos;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

/**
 * Admin › Catalog › Demo photos: the one couple photo set every wedding template demo shows (see
 * App\Support\DemoPhotos). Upload a photo per role; it goes to the s3 disk under demo/couple/. Customer
 * invitations never use these.
 *
 * @property-read Schema $form
 */
class DemoPhotos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Demo photos';

    protected static ?string $title = 'Demo photos';

    protected ?string $subheading = 'The couple photos every wedding template demo shows. Upload a new file for a role to swap it everywhere. Customer invitations never use these.';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Photos::all());
    }

    public function form(Schema $schema): Schema
    {
        $roles = collect(Photos::ROLES);

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Cover & couple')
                    ->description('Blank variants (tall / wide / closing) show the Cover instead.')
                    ->columns(3)
                    ->components($roles->only(['cover', 'cover_tall', 'cover_wide', 'groom', 'bride', 'end'])
                        ->map(fn ($role, $key) => $this->upload($key))->values()->all()),
                Section::make('Love story')
                    ->description('In order: story photo N is shown next to love-story chapter N. The caption also labels the photo in the gallery.')
                    ->columns(3)
                    ->components($roles->filter(fn ($r, $key) => str_starts_with($key, 'story_'))
                        ->map(fn ($role, $key) => $this->upload($key, true))->values()->all()),
                Section::make('Gallery')
                    ->description('Up to 12 photos. The caption is the line shown under the photo; blank = the built-in caption.')
                    ->columns(3)
                    ->collapsible()
                    ->components($roles->filter(fn ($r, $key) => str_starts_with($key, 'gallery_'))
                        ->map(fn ($role, $key) => $this->upload($key, true))->values()->all()),
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
                        Action::make('save')->label('Save demo photos')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $before = Photos::all()['photos'];
        $state = $this->form->getState();
        Photos::save($state);

        // a replaced or removed photo is deleted from the bucket (only our own folder)
        $after = Photos::all()['photos'];
        collect($before)->filter()
            ->reject(fn ($path) => in_array($path, $after, true))
            ->filter(fn ($path) => str_starts_with($path, Photos::DIRECTORY.'/'))
            ->each(fn ($path) => Storage::disk('s3')->delete($path));

        Notification::make()->title('Demo photos saved')->success()->send();
    }

    protected function upload(string $role, bool $withCaption = false): Grid
    {
        [$label, $hint, $caption] = Photos::ROLES[$role];

        return Grid::make(1)->components(array_filter([
            FileUpload::make("photos.{$role}")
                ->label($label)
                ->helperText($hint.'. WebP / JPG / PNG, max 2 MB.')
                ->image()
                ->acceptedFileTypes(['image/webp', 'image/jpeg', 'image/png'])
                ->disk('s3')
                ->directory(Photos::DIRECTORY)
                ->visibility('public')
                ->maxSize(2048),
            $withCaption
                ? TextInput::make("captions.{$role}")->hiddenLabel()->placeholder($caption)->maxLength(120)
                : null,
        ]));
    }
}
