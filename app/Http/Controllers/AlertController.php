<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    public function index(Request $request)
    {
        $query = Alert::with('product');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $alerts = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($alerts);
    }

    public function show(Alert $alert)
    {
        $alert->load('product');
        return response()->json($alert);
    }

    public function resolve(Alert $alert)
    {
        $this->alertService->resolveAlert($alert);

        return response()->json(['message' => 'Alerte résolue avec succès']);
    }

    public function dismiss(Alert $alert)
    {
        $alert->update([
            'status' => 'dismissed',
            'resolved_at' => now(),
        ]);

        return response()->json(['message' => 'Alerte ignorée']);
    }

    public function checkAll()
    {
        $this->alertService->checkAllProducts();

        return response()->json(['message' => 'Vérification des alertes terminée']);
    }
}
