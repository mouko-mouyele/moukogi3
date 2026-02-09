<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\PredictionService;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    protected PredictionService $predictionService;

    public function __construct(PredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    public function predict(Product $product, Request $request)
    {
        $days = $request->get('days', 30);
        
        if (!in_array($days, [7, 30, 90])) {
            $days = 30;
        }

        $prediction = $this->predictionService->predict($product, $days);

        return response()->json($prediction);
    }

    public function chartData(Product $product, Request $request)
    {
        $days = $request->get('days', 30);
        
        $data = $this->predictionService->getChartData($product, $days);

        return response()->json($data);
    }

    public function allPredictions(Request $request)
    {
        $days = $request->get('days', 30);
        $products = Product::all();
        
        $predictions = [];
        
        foreach ($products as $product) {
            $prediction = $this->predictionService->predict($product, $days);
            $predictions[] = [
                'product' => $product->load('category'),
                'prediction' => $prediction,
            ];
        }

        return response()->json($predictions);
    }
}
