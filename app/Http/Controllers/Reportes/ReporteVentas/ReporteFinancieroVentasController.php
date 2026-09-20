<?php

namespace App\Http\Controllers\Reportes\ReporteVentas;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\LoteDetalleVenta;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReporteFinancieroVentasController extends Controller
{
    public function flujoComprasVentas(Request $request)
    {
       
    }

    public function margenGanancia(Request $request)
    {
        
    }
}
