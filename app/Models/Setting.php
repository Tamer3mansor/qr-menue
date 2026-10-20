<?php

namespace App\Models;

use App\Observers\SettingObserver;
use Carbon\CarbonInterface;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'restaurant_name',
    'phone',
    'currency',
    'bg_image',
    'logo',
    'primary_color',
    'secondary_color',
    'hero_title',
    'hero_subtitle',
    'hero_image',
    'show_offers_ticker',
    'branches',
    'social_links',
    'seo_title',
    'seo_description',
    'seo_keywords',
    'primary_font',
    'working_hours',
    'payment_methods',
    'google_rating',
    'google_review_count',
    'google_maps_url',
    'chat_enabled',
    'chat_avatar_type',
    'chat_welcome_message',
])]
#[ObservedBy(SettingObserver::class)]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    public const DEFAULT_CURRENCY = 'ج.م';

    public const DISK = 'public';

    /**
     * The days of the week in the order the settings form displays them.
     *
     * @var array<string, string>
     */
    public const DAYS = [
        'saturday' => 'السبت',
        'sunday' => 'الأحد',
        'monday' => 'الاثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_offers_ticker' => 'boolean',
            // Structured website settings are read as arrays, never as raw JSON.
            'branches' => 'array',
            'social_links' => 'array',
            'working_hours' => 'array',
            'payment_methods' => 'array',
            'google_rating' => 'float',
            'google_review_count' => 'integer',
            'chat_enabled' => 'boolean',
        ];
    }

    /**
     * The fallback weekly schedule used until a tenant edits their working hours.
     *
     * @return array<string, array{open: string, close: string, closed: bool}>
     */
    public static function defaultWorkingHours(): array
    {
        $day = ['open' => '09:00', 'close' => '23:00', 'closed' => false];

        return [
            'saturday' => $day,
            'sunday' => $day,
            'monday' => $day,
            'tuesday' => $day,
            'wednesday' => $day,
            'thursday' => $day,
            'friday' => [...$day, 'closed' => true],
        ];
    }

    /**
     * The payment methods every tenant starts with. Only cash is enabled.
     *
     * @return list<array{type: string, label: string, value: ?string, enabled: bool}>
     */
    public static function defaultPaymentMethods(): array
    {
        return [
            ['type' => 'cash', 'label' => 'كاش', 'value' => null, 'enabled' => true],
            ['type' => 'visa', 'label' => 'فيزا/ماستركارد', 'value' => null, 'enabled' => false],
            ['type' => 'vodafone_cash', 'label' => 'فودافون كاش', 'value' => '01xxxxxxxxx', 'enabled' => false],
            ['type' => 'instapay', 'label' => 'انستاباي', 'value' => 'username', 'enabled' => false],
            ['type' => 'meeza', 'label' => 'ميزة', 'value' => null, 'enabled' => false],
        ];
    }

    /**
     * The stored schedule merged over the defaults, so every day is always
     * present in the settings form even on older rows.
     *
     * @return array<string, array{open: string, close: string, closed: bool}>
     */
    public function workingHours(): array
    {
        $stored = is_array($this->working_hours) ? $this->working_hours : [];

        $hours = [];

        foreach (self::defaultWorkingHours() as $day => $default) {
            $saved = is_array($stored[$day] ?? null) ? $stored[$day] : [];

            $hours[$day] = [
                'open' => (string) ($saved['open'] ?? $default['open']),
                'close' => (string) ($saved['close'] ?? $default['close']),
                'closed' => (bool) ($saved['closed'] ?? $default['closed']),
            ];
        }

        return $hours;
    }

    /**
     * The stored payment methods merged over the defaults, keyed by type.
     *
     * @return list<array{type: string, label: string, value: ?string, enabled: bool}>
     */
    public function paymentMethods(): array
    {
        $stored = is_array($this->payment_methods) ? $this->payment_methods : [];

        $byType = [];

        foreach ($stored as $method) {
            if (is_array($method) && filled($method['type'] ?? null)) {
                $byType[$method['type']] = $method;
            }
        }

        return array_map(function (array $default) use ($byType): array {
            $saved = $byType[$default['type']] ?? [];

            return [
                'type' => $default['type'],
                'label' => $default['label'],
                'value' => $saved['value'] ?? $default['value'],
                'enabled' => (bool) ($saved['enabled'] ?? $default['enabled']),
            ];
        }, self::defaultPaymentMethods());
    }

    /**
     * Only the methods the tenant turned on, in a stable order.
     *
     * @return list<array{type: string, label: string, value: ?string, enabled: bool}>
     */
    public function enabledPaymentMethods(): array
    {
        return array_values(array_filter(
            $this->paymentMethods(),
            fn (array $method): bool => $method['enabled'],
        ));
    }

    /**
     * The current day's opening times. The stored schedule is merged over the
     * defaults, which are the same hours the dashboard always shows.
     *
     * @return array{key: string, label: string, open: string, close: string, closed: bool}
     */
    public function todayWorkingHours(?CarbonInterface $now = null): array
    {
        $now ??= now(config('app.business_timezone'));

        $key = self::dayKeyFor($now);

        $day = $this->workingHours()[$key];

        return [
            'key' => $key,
            'label' => self::DAYS[$key],
            'open' => $day['open'],
            'close' => $day['close'],
            'closed' => $day['closed'],
        ];
    }

    /**
     * Whether the tenant is open right now, judged by the restaurant's local
     * time rather than the UTC timestamp the application stores.
     */
    public function isOpenNow(?CarbonInterface $now = null): bool
    {
        $now ??= now(config('app.business_timezone'));

        $today = $this->todayWorkingHours($now);

        $current = ((int) $now->hour * 60) + (int) $now->minute;

        if (! $today['closed']) {
            $open = self::minutesOfDay($today['open']);
            $close = self::minutesOfDay($today['close']);

            if ($open !== null && $close !== null) {
                if ($close > $open && $current >= $open && $current < $close) {
                    return true;
                }

                // A closing time earlier than the opening time means the shift
                // wraps past midnight.
                if ($close <= $open && $current >= $open) {
                    return true;
                }
            }
        }

        // An overnight shift that started yesterday may still be running.
        $previous = $this->todayWorkingHours($now->copy()->subDay());

        if (! $previous['closed']) {
            $previousOpen = self::minutesOfDay($previous['open']);
            $previousClose = self::minutesOfDay($previous['close']);

            if ($previousOpen !== null && $previousClose !== null
                && $previousClose < $previousOpen
                && $current < $previousClose) {
                return true;
            }
        }

        return false;
    }

    /**
     * Formats a "HH:MM" time for display in Arabic, e.g. "9 صباحاً".
     */
    public static function formatTimeOfDay(?string $time): ?string
    {
        $minutes = self::minutesOfDay($time);

        if ($minutes === null) {
            return null;
        }

        $hour = intdiv($minutes, 60);
        $minute = $minutes % 60;

        $period = $hour < 12 ? 'صباحاً' : 'مساءً';

        $displayHour = $hour % 12;
        $displayHour = $displayHour === 0 ? 12 : $displayHour;

        return $minute === 0
            ? "{$displayHour} {$period}"
            : "{$displayHour}:".str_pad((string) $minute, 2, '0', STR_PAD_LEFT)." {$period}";
    }

    protected static function dayKeyFor(CarbonInterface $now): string
    {
        return [
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
        ][(int) $now->dayOfWeek];
    }

    protected static function minutesOfDay(?string $time): ?int
    {
        if (blank($time) || ! preg_match('/^(\d{1,2}):(\d{2})/', $time, $matches)) {
            return null;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return ($hour * 60) + $minute;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve the currency to display for a given tenant owner.
     */
    public static function currencyFor(?User $owner): string
    {
        return $owner?->settings?->currency ?? self::DEFAULT_CURRENCY;
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->fileUrl($this->logo);
    }

    public function getBgImageUrlAttribute(): ?string
    {
        return $this->fileUrl($this->bg_image);
    }

    public function getHeroImageUrlAttribute(): ?string
    {
        return $this->fileUrl($this->hero_image);
    }

    /**
     * The social networks the tenant actually filled in, in a stable order.
     *
     * @return array<string, string>
     */
    public function filledSocialLinks(): array
    {
        return array_filter((array) $this->social_links, 'filled');
    }

    /**
     * The branches the tenant filled in, in the order they were entered.
     *
     * @return list<array<string, mixed>>
     */
    public function branchesList(): array
    {
        return array_values((array) $this->branches);
    }

    protected function fileUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }
}
