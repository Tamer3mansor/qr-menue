<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'order_number',
    'customer_name',
    'customer_phone',
    'status',
    'notes',
    'total',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Keeps two orders generated in the same second from sharing a number,
     * even before either has been persisted.
     */
    protected static int $orderNumberSequence = 0;

    /**
     * The customer-facing labels for every order status.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'pending' => 'في الانتظار',
        'confirmed' => 'تم الاستلام من المطعم',
        'preparing' => 'جاري التنفيذ',
        'on_the_way' => 'في الطريق للتوصيل',
        'delivered' => 'تم التسليم',
    ];

    /**
     * The Filament badge color for every order status.
     *
     * @var array<string, string>
     */
    public const STATUS_COLORS = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'preparing' => 'primary',
        'on_the_way' => 'warning',
        'delivered' => 'success',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * A short, human-readable number that stays unique per install.
     *
     * The attempt counter is folded into the hash so two orders created within
     * the same second still get different numbers.
     */
    public static function generateOrderNumber(int $tenantId): string
    {
        $attempt = 0;

        do {
            $orderNumber = 'ORD-'.strtoupper(substr(md5($tenantId.time().self::$orderNumberSequence.$attempt), 0, 8));

            $attempt++;
        } while (self::query()->where('order_number', $orderNumber)->exists());

        self::$orderNumberSequence++;

        return $orderNumber;
    }

    /**
     * The statuses a tenant can pick from when updating an order.
     *
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return self::STATUS_LABELS;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }
}
