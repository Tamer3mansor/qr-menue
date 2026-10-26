<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'user_id',
    'tenant_order_number',
    'branch_id',
    'customer_name',
    'customer_phone',
    'status',
    'notes',
    'delivery_type',
    'delivery_address',
    'total',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * The customer-facing labels for every delivery type.
     *
     * @var array<string, string>
     */
    public const DELIVERY_TYPE_LABELS = [
        'takeaway' => 'تيك أواي',
        'delivery' => 'دليفري',
    ];

    /**
     * The Filament badge color for every delivery type.
     *
     * @var array<string, string>
     */
    public const DELIVERY_TYPE_COLORS = [
        'takeaway' => 'gray',
        'delivery' => 'info',
    ];

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
            'tenant_order_number' => 'integer',
            'branch_id' => 'integer',
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
     * The next order number for a tenant, counted from 1.
     *
     * The row lock keeps two simultaneous submissions from reading the same
     * maximum; the unique index on the table is the final guarantee.
     */
    public static function generateOrderNumber(int $tenantId): int
    {
        return DB::transaction(function () use ($tenantId): int {
            $max = self::query()
                ->where('user_id', $tenantId)
                ->lockForUpdate()
                ->max('tenant_order_number');

            return ((int) $max) + 1;
        });
    }

    /**
     * The number customers see, always prefixed with a hash, e.g. "#47".
     */
    public function displayNumber(): string
    {
        return '#'.$this->tenant_order_number;
    }

    public function deliveryTypeLabel(): string
    {
        return self::DELIVERY_TYPE_LABELS[$this->delivery_type] ?? $this->delivery_type;
    }

    public function deliveryTypeColor(): string
    {
        return self::DELIVERY_TYPE_COLORS[$this->delivery_type] ?? 'gray';
    }

    /**
     * Branch names live in the tenant's settings JSON, so the stored id is the
     * branch's position in that list.
     */
    public function branchName(): ?string
    {
        if ($this->branch_id === null) {
            return null;
        }

        $branch = ($this->user?->settings?->branchesList() ?? [])[$this->branch_id] ?? null;

        return is_array($branch) ? ($branch['branch_name'] ?? null) : null;
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
