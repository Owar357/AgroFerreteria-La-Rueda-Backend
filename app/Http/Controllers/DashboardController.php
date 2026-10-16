<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    public function resumen(): JsonResponse
    {
        return response()->json($this->service->resumen());
    }

    public function ventasCompras(Request $request): JsonResponse
    {
        $data = $request->validate([
            'anio' => 'nullable|integer|between:2000,2100',
        ]);

        return response()->json(
            $this->service->ventasCompras((int) ($data['anio'] ?? now()->year))
        );
    }
}