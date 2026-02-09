<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PredictionService
{
    /**
     * Prédire les besoins futurs pour un produit
     */
    public function predict(Product $product, int $days = 30): array
    {
        $movements = $this->getHistoricalMovements($product, 90);
        
        if ($movements->count() < 2) {
            return $this->simplePrediction($product, $days);
        }

        if ($movements->count() > 100) {
            return $this->mlPrediction($product, $movements, $days);
        }

        return $this->movingAveragePrediction($product, $movements, $days);
    }

    /**
     * Prédiction simple basée sur le stock actuel
     */
    private function simplePrediction(Product $product, int $days): array
    {
        $dailyConsumption = $product->current_stock > 0 ? $product->current_stock / 30 : 0;
        $predictedConsumption = $dailyConsumption * $days;
        $predictedStock = max(0, $product->current_stock - $predictedConsumption);
        $riskOfRupture = $predictedStock < $product->stock_minimum;
        $recommendedOrder = $riskOfRupture ? max(0, $product->stock_optimal - $predictedStock) : 0;

        return [
            'current_stock' => $product->current_stock,
            'predicted_stock' => round($predictedStock, 2),
            'predicted_consumption' => round($predictedConsumption, 2),
            'risk_of_rupture' => $riskOfRupture,
            'recommended_order' => round($recommendedOrder),
            'method' => 'simple',
            'days' => $days,
        ];
    }

    /**
     * Prédiction par moyenne mobile
     */
    private function movingAveragePrediction(Product $product, Collection $movements, int $days): array
    {
        $sorties = $movements->where('type', 'sortie');
        $dailyConsumptions = $this->calculateDailyConsumption($sorties);
        
        if ($dailyConsumptions->isEmpty()) {
            return $this->simplePrediction($product, $days);
        }

        $averageDailyConsumption = $dailyConsumptions->average();
        $predictedConsumption = $averageDailyConsumption * $days;
        $predictedStock = max(0, $product->current_stock - $predictedConsumption);
        $riskOfRupture = $predictedStock < $product->stock_minimum;
        $recommendedOrder = $riskOfRupture ? max(0, $product->stock_optimal - $predictedStock) : 0;

        return [
            'current_stock' => $product->current_stock,
            'predicted_stock' => round($predictedStock, 2),
            'predicted_consumption' => round($predictedConsumption, 2),
            'average_daily_consumption' => round($averageDailyConsumption, 2),
            'risk_of_rupture' => $riskOfRupture,
            'recommended_order' => round($recommendedOrder),
            'method' => 'moving_average',
            'days' => $days,
            'historical_data_points' => $movements->count(),
        ];
    }

    /**
     * Prédiction par régression linéaire (ML léger)
     */
    private function mlPrediction(Product $product, Collection $movements, int $days): array
    {
        $sorties = $movements->where('type', 'sortie');
        $dailyConsumptions = $this->calculateDailyConsumption($sorties);
        
        if ($dailyConsumptions->count() < 7) {
            return $this->movingAveragePrediction($product, $movements, $days);
        }

        // Régression linéaire simple
        $regression = $this->linearRegression($dailyConsumptions);
        $trend = $regression['slope'];
        $intercept = $regression['intercept'];
        
        // Prédiction future
        $futureDays = range(1, $days);
        $predictedDailyConsumptions = array_map(function($day) use ($trend, $intercept) {
            return $intercept + ($trend * $day);
        }, $futureDays);

        $predictedConsumption = array_sum($predictedDailyConsumptions);
        $predictedStock = max(0, $product->current_stock - $predictedConsumption);
        $riskOfRupture = $predictedStock < $product->stock_minimum;
        $recommendedOrder = $riskOfRupture ? max(0, $product->stock_optimal - $predictedStock) : 0;

        return [
            'current_stock' => $product->current_stock,
            'predicted_stock' => round($predictedStock, 2),
            'predicted_consumption' => round($predictedConsumption, 2),
            'trend' => round($trend, 4),
            'risk_of_rupture' => $riskOfRupture,
            'recommended_order' => round($recommendedOrder),
            'method' => 'linear_regression',
            'days' => $days,
            'historical_data_points' => $movements->count(),
        ];
    }

    /**
     * Calculer la consommation quotidienne
     */
    private function calculateDailyConsumption(Collection $sorties): Collection
    {
        $grouped = $sorties->groupBy(function($movement) {
            return $movement->movement_date->format('Y-m-d');
        });

        return $grouped->map(function($dayMovements) {
            return $dayMovements->sum('quantity');
        })->values();
    }

    /**
     * Régression linéaire simple
     */
    private function linearRegression(Collection $data): array
    {
        $n = $data->count();
        $x = range(1, $n);
        $y = $data->values()->all();

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
        ];
    }

    /**
     * Obtenir les mouvements historiques
     */
    private function getHistoricalMovements(Product $product, int $days): Collection
    {
        $startDate = Carbon::now()->subDays($days);
        
        return StockMovement::where('product_id', $product->id)
            ->where('movement_date', '>=', $startDate)
            ->orderBy('movement_date', 'asc')
            ->get();
    }

    /**
     * Obtenir les données pour les graphiques
     */
    public function getChartData(Product $product, int $days = 30): array
    {
        $movements = $this->getHistoricalMovements($product, 90);
        $prediction = $this->predict($product, $days);
        
        $historical = [];
        $currentStock = $product->current_stock;
        
        foreach ($movements->sortBy('movement_date') as $movement) {
            if ($movement->type === 'entree') {
                $currentStock += $movement->quantity;
            } else {
                $currentStock -= $movement->quantity;
            }
            
            $historical[] = [
                'date' => $movement->movement_date->format('Y-m-d'),
                'stock' => max(0, $currentStock),
            ];
        }

        // Ajouter les prédictions futures
        $future = [];
        $startDate = Carbon::now();
        $predictedStock = $product->current_stock;
        
        for ($i = 1; $i <= $days; $i++) {
            $dailyConsumption = $prediction['predicted_consumption'] / $days;
            $predictedStock = max(0, $predictedStock - $dailyConsumption);
            
            $future[] = [
                'date' => $startDate->copy()->addDays($i)->format('Y-m-d'),
                'stock' => round($predictedStock, 2),
            ];
        }

        return [
            'historical' => $historical,
            'prediction' => $future,
            'current_stock' => $product->current_stock,
            'stock_minimum' => $product->stock_minimum,
        ];
    }
}
