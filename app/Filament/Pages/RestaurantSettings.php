<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\User;
use App\Services\ColorExtractorService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use LogicException;

class RestaurantSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'إعدادات المطعم';

    protected static ?string $title = 'إعدادات المطعم';

    /**
     * Navigation items fall back to a sort of -1, so a high value keeps this
     * page below every resource in the sidebar.
     */
    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.restaurant-settings';

    protected ?Setting $setting = null;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    /**
     * The palette last pulled out of the logo, kept between the modal render
     * and the submit that applies it.
     *
     * @var array{primary: string, secondary: string} | null
     */
    public ?array $extractedColors = null;

    public function mount(): void
    {
        $this->form->fill($this->setting()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                // The Form component is what emits the <form wire:submit="save">
                // element the footer save button submits through.
                Form::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('restaurant_name')
                                    ->label('اسم المطعم')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->label('الهاتف')
                                    ->maxLength(255),
                                TextInput::make('currency')
                                    ->label('العملة')
                                    ->default(Setting::DEFAULT_CURRENCY)
                                    ->maxLength(255),
                                ColorPicker::make('primary_color')
                                    ->label('اللون الأساسي')
                                    ->hex()
                                    ->default('#000000'),
                                ColorPicker::make('secondary_color')
                                    ->label('اللون الثانوي')
                                    ->hex()
                                    ->default('#ffffff'),
                                FileUpload::make('logo')
                                    ->label('الشعار')
                                    ->image()
                                    ->disk('public')
                                    ->directory('logos')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(2048),
                                FileUpload::make('bg_image')
                                    ->label('صورة الخلفية')
                                    ->image()
                                    ->disk('public')
                                    ->directory('backgrounds')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120),
                            ]),
                    ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        $this->getSaveAction(),
                    ]),
            ]);
    }

    public function save(): void
    {
        $this->setting()->update($this->form->getState());

        Notification::make()
            ->title('تم حفظ الإعدادات.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->extractLogoColorsAction(),
        ];
    }

    /**
     * Offers a palette read from the saved logo. Applying only fills the form
     * state, so nothing reaches the database until the normal save runs.
     */
    protected function extractLogoColorsAction(): Action
    {
        return Action::make('extractLogoColors')
            ->label('استخلاص الألوان من الشعار')
            ->icon(Heroicon::OutlinedSwatch)
            ->color('warning')
            ->visible(fn (): bool => filled($this->setting()->logo))
            ->modalHeading('الألوان المقترحة من الشعار')
            ->modalSubmitActionLabel('تطبيق الألوان')
            ->modalCancelActionLabel('تجاهل')
            ->modalContent(function (): View {
                $this->extractedColors = null;

                return view('filament.pages.extracted-colors', [
                    'colors' => $this->runColorExtraction(),
                ]);
            })
            ->action(function (): void {
                $colors = $this->extractedColors ?? $this->runColorExtraction();

                if ($colors === []) {
                    Notification::make()
                        ->title('تعذر استخلاص الألوان من الشعار.')
                        ->danger()
                        ->send();

                    return;
                }

                $this->form->fill(array_merge($this->form->getState(), [
                    'primary_color' => $colors['primary'],
                    'secondary_color' => $colors['secondary'],
                ]));

                Notification::make()
                    ->title('تم تطبيق الألوان — اضغط حفظ لاعتمادها.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array{primary: string, secondary: string}
     */
    protected function runColorExtraction(): array
    {
        $extractor = app(ColorExtractorService::class);

        if (! $extractor->isSupported()) {
            Notification::make()
                ->title('هذه الميزة غير متاحة حالياً.')
                ->warning()
                ->send();

            return [];
        }

        return $this->extractedColors = $extractor->extractFromImage($this->logoPath());
    }

    protected function logoPath(): string
    {
        return Storage::disk(Setting::DISK)->path((string) $this->setting()->logo);
    }

    protected function getSaveAction(): Action
    {
        return Action::make('save')
            ->label('حفظ')
            ->submit('save');
    }

    /**
     * Every tenant owns exactly one settings row, which is created on first visit.
     *
     * The instance is refreshed so that database-level defaults are present in
     * the hydrated form state.
     */
    protected function setting(): Setting
    {
        return $this->setting ??= Setting::query()->firstOrCreate(
            ['user_id' => $this->tenant()->getKey()],
            ['restaurant_name' => $this->tenant()->name],
        )->refresh();
    }

    protected function tenant(): User
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof User) {
            throw new LogicException('The restaurant settings page requires a resolved tenant.');
        }

        return $tenant;
    }
}
