<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Créer un nouvel inventaire
     */
    public function create(string $reference, int $userId, ?string $notes = null): Inventory
    {
        return Inventory::create([
            'reference' => $reference,
            'inventory_date' => now(),
            'user_id' => $userId,
            'notes' => $notes,
            'status' => 'en_cours',
        ]);
    }

    /**
     * Ajouter un article à l'inventaire
     */
    public function addItem(Inventory $inventory, Product $product, int $actualQuantity, ?string $justification = null): InventoryItem
    {
        return $inventory->items()->create([
            'product_id' => $product->id,
            'theoretical_quantity' => $product->current_stock,
            'actual_quantity' => $actualQuantity,
            'difference' => 0, // Sera calculé au moment de compléter
            'justification' => $justification,
        ]);
    }

    /**
     * Mettre à jour la quantité constatée d'un article
     */
    public function updateItemQuantity(InventoryItem $item, int $actualQuantity, ?string $justification = null): bool
    {
        $item->actual_quantity = $actualQuantity;
        if ($justification !== null) {
            $item->justification = $justification;
        }
        return $item->save();
    }

    /**
     * Terminer l'inventaire (calcul des écarts et changement de statut)
     */
    public function complete(Inventory $inventory): Inventory
    {
        DB::transaction(function () use ($inventory) {
            // Calculer les écarts
            $inventory->calculateDifferences();

            // Passer au statut terminé
            $inventory->markAsCompleted();

            // Ajuster les stocks en fonction des écarts
            $this->adjustStocks($inventory);
        });

        return $inventory->fresh();
    }

    /**
     * Ajuster automatiquement les stocks suite à l'inventaire
     */
    private function adjustStocks(Inventory $inventory): void
    {
        foreach ($inventory->items as $item) {
            if ($item->difference != 0) {
                // Enregistrer le mouvement de stock
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'movement_type' => $item->difference > 0 ? 'adjustment_increase' : 'adjustment_decrease',
                    'quantity' => abs($item->difference),
                    'notes' => "Ajustement suite à inventaire #{$inventory->reference}",
                ]);

                // Mettre à jour le stock du produit
                $product = $item->product;
                $product->current_stock += $item->difference;
                $product->save();
            }
        }
    }

    /**
     * Archiver l'inventaire
     */
    public function archiveInventory(Inventory $inventory): Inventory
    {
        if (!$inventory->isCompleted()) {
            throw new \Exception('Seul un inventaire terminé peut être archivé');
        }

        $inventory->archive();
        return $inventory;
    }

    /**
     * Obtenir le résumé d'un inventaire
     */
    public function getSummary(Inventory $inventory): array
    {
        $items = $inventory->items;
        $itemsWithDifference = $inventory->getItemsWithDifference();

        return [
            'reference' => $inventory->reference,
            'status' => $inventory->status,
            'date' => $inventory->inventory_date,
            'user' => $inventory->user->name,
            'total_items' => $items->count(),
            'items_with_difference' => $itemsWithDifference->count(),
            'total_difference' => $inventory->getTotalDifference(),
            'accuracy_percentage' => $items->count() > 0 ?
                round(((($items->count() - $itemsWithDifference->count()) / $items->count()) * 100), 2) : 0,
        ];
    }
}
