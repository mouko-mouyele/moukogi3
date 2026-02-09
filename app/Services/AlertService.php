<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Product;
use App\Services\PredictionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AlertService
{
    protected PredictionService $predictionService;

    public function __construct(PredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    /**
     * Vérifier et créer les alertes pour tous les produits
     */
    public function checkAllProducts(): void
    {
        $products = Product::all();
        
        foreach ($products as $product) {
            $this->checkProduct($product);
        }
    }

    /**
     * Vérifier les alertes pour un produit spécifique
     */
    public function checkProduct(Product $product): void
    {
        // Vérifier stock minimum
        if ($product->current_stock <= $product->stock_minimum) {
            $this->createAlert($product, 'stock_minimum', 
                'Stock minimum atteint', 
                "Le produit {$product->name} a atteint son stock minimum ({$product->stock_minimum} unités). Stock actuel : {$product->current_stock}");
        }

        // Vérifier risque de rupture
        $prediction = $this->predictionService->predict($product, 7);
        if ($prediction['risk_of_rupture']) {
            $this->createAlert($product, 'rupture_imminente',
                'Rupture de stock imminente',
                "Le produit {$product->name} risque d'être en rupture dans les 7 prochains jours. Stock prévu : {$prediction['predicted_stock']}");
        }

        // Vérifier expiration proche
        if ($product->expiration_date && $product->expiration_date->isFuture()) {
            $daysUntilExpiration = Carbon::now()->diffInDays($product->expiration_date);
            if ($daysUntilExpiration <= 30 && $daysUntilExpiration > 0) {
                $this->createAlert($product, 'expiration_proche',
                    'Expiration proche',
                    "Le produit {$product->name} expire dans {$daysUntilExpiration} jour(s) ({$product->expiration_date->format('d/m/Y')})");
            }
        }

        // Vérifier surstock
        if ($product->stock_optimal > 0 && $product->current_stock > $product->stock_optimal * 1.5) {
            $this->createAlert($product, 'surstock',
                'Surstock détecté',
                "Le produit {$product->name} est en surstock. Stock actuel : {$product->current_stock}, Stock optimal : {$product->stock_optimal}");
        }
    }

    /**
     * Créer une alerte
     */
    private function createAlert(Product $product, string $type, string $title, string $message): void
    {
        // Vérifier si une alerte active existe déjà
        $existingAlert = Alert::where('product_id', $product->id)
            ->where('type', $type)
            ->where('status', 'active')
            ->first();

        if (!$existingAlert) {
            Alert::create([
                'product_id' => $product->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'status' => 'active',
                'email_sent' => false,
            ]);

            // TODO: Envoyer un email si configuré
            // $this->sendEmailAlert($alert);
        }
    }

    /**
     * Résoudre une alerte
     */
    public function resolveAlert(Alert $alert): void
    {
        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Obtenir les alertes actives
     */
    public function getActiveAlerts(): \Illuminate\Database\Eloquent\Collection
    {
        return Alert::where('status', 'active')
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
