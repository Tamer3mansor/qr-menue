<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use LogicException;

/**
 * The public website options for a tenant: the hero, the offers ticker, branches,
 * social links, SEO and the look and feel.
 *
 * Deliberately separate from RestaurantSettings, which holds the practical
 * details a restaurant changes often (name, phone, logo).
 */
class WebsiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'إعدادات الموقع';

    protected static ?string $title = 'إعدادات الموقع';

    /**
     * Navigation items fall back to a sort of -1, so this sits below
     * RestaurantSettings.
     */
    protected static ?int $navigationSort = 101;

    protected string $view = 'filament.pages.website-settings';

    protected ?Setting $setting = null;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    /**
     * The Arabic web fonts a menu can be rendered with.
     *
     * @var list<string>
     */
    public const FONTS = [
        'Cairo',
        'Tajawal',
        'Almarai',
        'Noto Sans Arabic',
    ];

    /**
     * Select options are value => label pairs, so a plain list would store the
     * array index as the font name.
     *
     * @return array<string, string>
     */
    public static function fontOptions(): array
    {
        return array_combine(self::FONTS, self::FONTS);
    }

    /**
     * SEO snippets are truncated by Google, so the fields are capped at the
     * lengths it actually renders rather than the column width.
     */
    public const SEO_TITLE_LIMIT = 60;

    public const SEO_DESCRIPTION_LIMIT = 160;

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
                                Tabs::make()
                                    ->tabs([
                                        $this->heroTab(),
                                        $this->offersTickerTab(),
                                        $this->branchesTab(),
                                        $this->socialMediaTab(),
                                        $this->seoTab(),
                                        $this->appearanceTab(),
                                    ]),
                            ]),
                    ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        $this->getSaveAction(),
                    ]),
            ]);
    }

    protected function heroTab(): Tab
    {
        return Tab::make('الواجهة الرئيسية')
            ->icon(Heroicon::OutlinedPhoto)
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('hero_title')
                            ->label('عنوان الواجهة')
                            ->maxLength(255),
                        Textarea::make('hero_subtitle')
                            ->label('عنوان فرعي')
                            ->rows(3)
                            ->maxLength(65535),
                        FileUpload::make('hero_image')
                            ->label('صورة الواجهة')
                            ->image()
                            ->disk('public')
                            ->directory('hero')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048),
                    ])
                    ->columns(1),
            ]);
    }

    protected function offersTickerTab(): Tab
    {
        return Tab::make('شريط العروض')
            ->icon(Heroicon::OutlinedTag)
            ->schema([
                Section::make()
                    ->schema([
                        Toggle::make('show_offers_ticker')
                            ->label('عرض شريط العروض المتحرك')
                            ->helperText('يظهر شريط متحرك أعلى القائمة بأحدث العروض.')
                            ->default(true),
                    ])
                    ->columns(1),
            ]);
    }

    protected function branchesTab(): Tab
    {
        return Tab::make('الفروع')
            ->icon(Heroicon::OutlinedBuildingStorefront)
            ->schema([
                Section::make()
                    ->schema([
                        Repeater::make('branches')
                            ->label('الفروع')
                            ->schema([
                                TextInput::make('branch_name')
                                    ->label('اسم الفرع')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('address')
                                    ->label('العنوان')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->label('الهاتف')
                                    ->tel()
                                    ->maxLength(255),
                            ])
                            ->columns(3)
                            ->itemLabel(fn (array $state): ?string => $state['branch_name'] ?? null)
                            ->defaultItems(0)
                            ->addActionLabel('إضافة فرع')
                            ->reorderable(),
                    ])
                    ->columns(1),
            ]);
    }

    protected function socialMediaTab(): Tab
    {
        return Tab::make('التواصل الاجتماعي')
            ->icon(Heroicon::OutlinedShare)
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('social_links.facebook')
                            ->label('فيسبوك')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_links.instagram')
                            ->label('إنستجرام')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_links.tiktok')
                            ->label('تيك توك')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    protected function seoTab(): Tab
    {
        return Tab::make('السيو')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('عنوان السيو')
                            ->hint('عنوان الصفحة في جوجل')
                            ->maxLength(self::SEO_TITLE_LIMIT),
                        Textarea::make('seo_description')
                            ->label('وصف السيو')
                            ->hint('الوصف الظاهر في نتائج البحث')
                            ->rows(3)
                            ->maxLength(self::SEO_DESCRIPTION_LIMIT),
                        TextInput::make('seo_keywords')
                            ->label('كلمات السيو')
                            ->hint('كلمات مفتاحية مفصولة بفواصل')
                            ->maxLength(255),
                    ])
                    ->columns(1),
            ]);
    }

    protected function appearanceTab(): Tab
    {
        return Tab::make('الألوان والخط')
            ->icon(Heroicon::OutlinedSwatch)
            ->schema([
                Section::make()
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label('اللون الأساسي')
                            ->hex(),
                        ColorPicker::make('secondary_color')
                            ->label('اللون الثانوي')
                            ->hex(),
                        Select::make('primary_font')
                            ->label('الخط')
                            ->options(self::fontOptions())
                            ->default('Cairo')
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }

    public function save(): void
    {
        $this->setting()->update($this->form->getState());

        Notification::make()
            ->title('تم حفظ إعدادات الموقع.')
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
            throw new LogicException('The website settings page requires a resolved tenant.');
        }

        return $tenant;
    }
}
