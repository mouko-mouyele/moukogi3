<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'barcode',
        'supplier',
        'price',
        'category_id',
        'stock_minimum',
        'stock_optimal',
        'current_stock',
        'technical_sheet_path',
        'expiration_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'expiration_date' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function updateStock(int $quantity, string $type): void
    {
        if ($type === 'entree') {
            $this->current_stock += $quantity;
        } else {
            $this->current_stock -= $quantity;
        }
        $this->save();
    }
}
