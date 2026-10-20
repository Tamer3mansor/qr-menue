<?php

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetRestaurantInfoTool implements Tool
{
    public function __construct(protected int $tenantId) {}

    public function description(): Stringable|string
    {
        return 'يجيب معلومات النشاط: ساعات العمل وطرق الدفع والتواصل.';
    }

    public function handle(Request $request): Stringable|string
    {
        $settings = User::query()->with('settings')->find($this->tenantId)?->settings;

        if ($settings === null) {
            return 'لا توجد بيانات متاحة';
        }

        return (string) json_encode([
            'phone' => $settings->phone,
            'working_hours' => $settings->workingHours(),
            'payment_methods' => $settings->enabledPaymentMethods(),
            'is_open_now' => $settings->isOpenNow(),
            'social_links' => $settings->filledSocialLinks(),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
