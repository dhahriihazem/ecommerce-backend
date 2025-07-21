<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'price',
        'stock_quantity',
        'starting_price',
        'current_highest_bid',
        'auction_end_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => ProductType::class,
        'auction_end_time' => 'datetime',
        'price' => 'decimal:2',
        'starting_price' => 'decimal:2',
        'current_highest_bid' => 'decimal:2',
    ];

    /**
     * Check if the product is a fixed price type.
     */
    public function isFixedPrice(): bool
    {
        return $this->type === ProductType::FixedPrice;
    }

    /**
     * Check if the product is an auction type.
     */
    public function isAuction(): bool
    {
        return $this->type === ProductType::Auction;
    }

    /**
     * Get the bids for the auction product.
     */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    /**
     * The orders that contain the product.
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }
}