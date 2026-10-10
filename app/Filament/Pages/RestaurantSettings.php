<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\User;
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
