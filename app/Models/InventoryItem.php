<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'product_id',
        'theoretical_quantity',
        'actual_quantity',
        'difference',
        'justification',
    ];

    protected $casts = [
        'theoretical_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'difference' => 'integer',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculer l'écart (théorique - constaté)
     */
    public function calculateDifference(): void
    {
        $this->difference = $this->theoretical_quantity - $this->actual_quantity;
        $this->save();
    }

    /**
     * Vérifier s'il y a un écart
     */
    public function hasDifference(): bool
    {
        return $this->difference != 0;
    }

    /**
     * Vérifier si la quantité réelle est plus élevée (surplus)
     */
    public function isSurplus(): bool
    {
        return $this->difference < 0;
    }

    /**
     * Vérifier si la quantité réelle est plus basse (manque)
     */
    public function isShortage(): bool
    {
        return $this->difference > 0;
    }

    /**
     * Obtenir l'écart en valeur absolue
     */
    public function getAbsoluteDifference(): int
    {
        return abs($this->difference);
    }

    /**
     * Obtenir le pourcentage d'écart par rapport à la quantité théorique
     */
    public function getDifferencePercentage(): float
    {
        if ($this->theoretical_quantity == 0) {
            return 0;
        }
        return round(($this->difference / $this->theoretical_quantity) * 100, 2);
    }

    /**
     * Obtenir le statut de conformité de l'article
     */
    public function getComplianceStatus(): string
    {
        if ($this->difference == 0) {
            return 'conforme';
        } elseif ($this->isSurplus()) {
            return 'surplus';
        } else {
            return 'manque';
        }
    }

    /**
     * Mettre à jour la quantité constatée et recalculer l'écart
     */
    public function updateActualQuantity(int $quantity, ?string $justification = null): bool
    {
        $this->actual_quantity = $quantity;
        if ($justification !== null) {
            $this->justification = $justification;
        }
        $this->calculateDifference();
        return true;
    }

    /**
     * Obtenir le résumé de l'article
     */
    public function getSummary(): array
    {
        return [
            'product_name' => $this->product->name,
            'theoretical_quantity' => $this->theoretical_quantity,
            'actual_quantity' => $this->actual_quantity,
            'difference' => $this->difference,
            'percentage_difference' => $this->getDifferencePercentage(),
            'status' => $this->getComplianceStatus(),
            'justification' => $this->justification,
        ];
    }
}
