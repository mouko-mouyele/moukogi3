<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Alert;
use App\Models\Category;
use App\Services\PredictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected PredictionService $predictionService;

    public function __construct(PredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    public function index()
    {
        // Statistiques générales
        $totalProducts = Product::count();
        $totalValue = Product::sum(DB::raw('current_stock * price'));
        $lowStockProducts = Product::whereColumn('current_stock', '<=', 'stock_minimum')->count();
        $activeAlerts = Alert::where('status', 'active')->count();

        // Produits proches de la rupture
        $productsNearRupture = Product::whereColumn('current_stock', '<=', 'stock_minimum')
            ->with('category')
            ->orderBy('current_stock', 'asc')
            ->limit(10)
            ->get();

        // Mouvements récents
        $recentMovements = StockMovement::with('product', 'user')
            ->orderBy('movement_date', 'desc')
            ->limit(10)
            ->get();

        // Alertes actives
        $alerts = Alert::where('status', 'active')
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Graphiques - Évolution des mouvements par type (30 derniers jours)
        $movementsByType = StockMovement::select('type', DB::raw('SUM(quantity) as total'))
            ->where('movement_date', '>=', now()->subDays(30))
            ->groupBy('type')
            ->get();

        // Graphiques - Mouvements par catégorie
        $movementsByCategory = StockMovement::join('products', 'stock_movements.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('SUM(CASE WHEN stock_movements.type = "sortie" THEN stock_movements.quantity ELSE 0 END) as sorties'))
            ->where('stock_movements.movement_date', '>=', now()->subDays(30))
            ->groupBy('categories.id', 'categories.name')
            ->get();

        // Taux de rotation (simplifié)
        $rotationRate = $this->calculateRotationRate();

        return response()->json([
            'statistics' => [
                'total_products' => $totalProducts,
                'total_value' => round($totalValue, 2),
                'low_stock_products' => $lowStockProducts,
                'active_alerts' => $activeAlerts,
                'rotation_rate' => $rotationRate,
            ],
            'products_near_rupture' => $productsNearRupture,
            'recent_movements' => $recentMovements,
            'alerts' => $alerts,
            'charts' => [
                'movements_by_type' => $movementsByType,
                'movements_by_category' => $movementsByCategory,
            ],
        ]);
    }

    private function calculateRotationRate(): float
    {
        $totalSorties = StockMovement::where('type', 'sortie')
            ->where('movement_date', '>=', now()->subDays(30))
            ->sum('quantity');

        $averageStock = Product::avg('current_stock');

        if ($averageStock == 0) {
            return 0;
        }

        return round(($totalSorties / $averageStock) * 100, 2);
    }
}
