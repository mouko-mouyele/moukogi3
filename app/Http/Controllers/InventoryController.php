<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    public function index()
    {
        $inventories = Inventory::with('user', 'items.product')
            ->orderBy('inventory_date', 'desc')
            ->paginate(15);

        return response()->json($inventories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.actual_quantity' => 'required|integer|min:0',
            'items.*.justification' => 'nullable|string',
        ]);

        $inventory = Inventory::create([
            'reference' => 'INV-' . strtoupper(Str::random(8)),
            'inventory_date' => $validated['inventory_date'],
            'user_id' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
            'status' => 'en_cours',
        ]);

        foreach ($validated['items'] as $item) {
            $product = Product::findOrFail($item['product_id']);
            $difference = $item['actual_quantity'] - $product->current_stock;

            InventoryItem::create([
                'inventory_id' => $inventory->id,
                'product_id' => $item['product_id'],
                'theoretical_quantity' => $product->current_stock,
                'actual_quantity' => $item['actual_quantity'],
                'difference' => $difference,
                'justification' => $item['justification'] ?? null,
            ]);

            // Ajuster le stock
            $product->current_stock = $item['actual_quantity'];
            $product->save();

            // Créer un mouvement de correction
            if ($difference != 0) {
                \App\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'type' => $difference > 0 ? 'entree' : 'sortie',
                    'motion_type' => 'correction',
                    'quantity' => abs($difference),
                    'movement_date' => $validated['inventory_date'],
                    'user_id' => $request->user()->id,
                    'reason' => 'Ajustement inventaire : ' . ($item['justification'] ?? 'Aucune justification'),
                ]);
            }

            // Vérifier les alertes
            $this->alertService->checkProduct($product);
        }

        $inventory->status = 'termine';
        $inventory->save();

        $inventory->load('user', 'items.product');

        return response()->json($inventory, 201);
    }

    public function show(Inventory $inventory)
    {
        $inventory->load('user', 'items.product');
        return response()->json($inventory);
    }

    public function update(Request $request, Inventory $inventory)
    {
        if ($inventory->status === 'archive') {
            return response()->json([
                'message' => 'Impossible de modifier un inventaire archivé'
            ], 422);
        }

        $validated = $request->validate([
            'status' => 'required|in:en_cours,termine,archive',
            'notes' => 'nullable|string',
        ]);

        $inventory->update($validated);

        return response()->json($inventory);
    }

    public function destroy(Inventory $inventory)
    {
        if ($inventory->status === 'archive') {
            return response()->json([
                'message' => 'Impossible de supprimer un inventaire archivé'
            ], 422);
        }

        // Restaurer les stocks
        foreach ($inventory->items as $item) {
            $product = $item->product;
            $product->current_stock = $item->theoretical_quantity;
            $product->save();
        }

        $inventory->delete();

        return response()->json(['message' => 'Inventaire supprimé avec succès']);
    }
}
