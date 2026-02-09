<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\AlertService;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    public function index(Request $request)
    {
        $query = StockMovement::with('product', 'user');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('date_from')) {
            $query->where('movement_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('movement_date', '<=', $request->date_to);
        }

        $movements = $query->orderBy('movement_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($movements);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:entree,sortie',
            'motion_type' => 'required|in:achat,retour,correction,vente,perte,casse,expiration',
            'quantity' => 'required|integer|min:1',
            'movement_date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Vérifier si c'est une sortie et si le stock est suffisant
        if ($validated['type'] === 'sortie' && $product->current_stock < $validated['quantity']) {
            return response()->json([
                'message' => 'Stock insuffisant. Stock disponible : ' . $product->current_stock
            ], 422);
        }

        $validated['user_id'] = $request->user()->id;

        $movement = StockMovement::create($validated);

        // Mettre à jour le stock du produit
        $product->updateStock($validated['quantity'], $validated['type']);

        // Vérifier les alertes
        $this->alertService->checkProduct($product);

        $movement->load('product', 'user');

        return response()->json($movement, 201);
    }

    public function show(StockMovement $stockMovement)
    {
        $stockMovement->load('product', 'user');
        return response()->json($stockMovement);
    }

    public function update(Request $request, StockMovement $stockMovement)
    {
        $validated = $request->validate([
            'type' => 'required|in:entree,sortie',
            'motion_type' => 'required|in:achat,retour,correction,vente,perte,casse,expiration',
            'quantity' => 'required|integer|min:1',
            'movement_date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $product = $stockMovement->product;

        // Annuler l'ancien mouvement
        $oldType = $stockMovement->type === 'entree' ? 'sortie' : 'entree';
        $product->updateStock($stockMovement->quantity, $oldType);

        // Appliquer le nouveau mouvement
        if ($validated['type'] === 'sortie' && $product->current_stock < $validated['quantity']) {
            // Restaurer l'ancien mouvement
            $product->updateStock($stockMovement->quantity, $stockMovement->type);
            return response()->json([
                'message' => 'Stock insuffisant. Stock disponible : ' . $product->current_stock
            ], 422);
        }

        $stockMovement->update($validated);
        $product->updateStock($validated['quantity'], $validated['type']);

        // Vérifier les alertes
        $this->alertService->checkProduct($product);

        $stockMovement->load('product', 'user');

        return response()->json($stockMovement);
    }

    public function destroy(StockMovement $stockMovement)
    {
        $product = $stockMovement->product;

        // Annuler le mouvement
        $reverseType = $stockMovement->type === 'entree' ? 'sortie' : 'entree';
        $product->updateStock($stockMovement->quantity, $reverseType);

        $stockMovement->delete();

        // Vérifier les alertes
        $this->alertService->checkProduct($product);

        return response()->json(['message' => 'Mouvement supprimé avec succès']);
    }
}
