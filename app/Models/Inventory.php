<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'inventory_date',
        'user_id',
        'notes',
        'status',
    ];

    protected $casts = [
        'inventory_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Calculer automatiquement les écarts pour tous les articles
     */
    public function calculateDifferences(): void
    {
        foreach ($this->items as $item) {
            $item->difference = $item->theoretical_quantity - $item->actual_quantity;
            $item->save();
        }
    }

    /**
     * Marquer l'inventaire comme terminé
     */
    public function markAsCompleted(): bool
    {
        $this->calculateDifferences();
        $this->status = 'termine';
        return $this->save();
    }

    /**
     * Archiver l'inventaire
     */
    public function archive(): bool
    {
        $this->status = 'archive';
        return $this->save();
    }

    /**
     * Vérifier si l'inventaire est en cours
     */
    public function isInProgress(): bool
    {
        return $this->status === 'en_cours';
    }

    /**
     * Vérifier si l'inventaire est terminé
     */
    public function isCompleted(): bool
    {
        return $this->status === 'termine';
    }

    /**
     * Vérifier si l'inventaire est archivé
     */
    public function isArchived(): bool
    {
        return $this->status === 'archive';
    }

    /**
     * Obtenir les articles avec écart (articles qui diffèrent)
     */
    public function getItemsWithDifference(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->items->filter(function ($item) {
            return $item->difference != 0;
        });
    }

    /**
     * Obtenir l'écart total de l'inventaire
     */
    public function getTotalDifference(): int
    {
        return $this->items->sum('difference');
    }
}
