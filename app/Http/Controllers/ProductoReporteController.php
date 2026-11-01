<?php

namespace App\Http\Controllers;

use App\Exports\ProductosMasVendidosExport;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Support\SedeDB as DB;
use Maatwebsite\Excel\Facades\Excel;

class ProductoReporteController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:gestionar-productos-admin');
    }

    public function escasos(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolveRangoFechas($request);
        $estado = (string) $request->query('estado', 'activos');

        $query = DB::table('productos as p')
            ->select([
                'p.id',
                'p.nombre',
                'p.codigo',
                'p.costo',
                'p.precio',
                'p.stock',
                'p.stock_minimo',
                'p.activo',
                DB::raw('CASE WHEN p.stock <= 0 THEN 1 ELSE 0 END as es_agotado'),
                DB::raw('CASE WHEN p.stock > 0 AND p.stock <= p.stock_minimo THEN 1 ELSE 0 END as es_escaso'),
            ])
            ->where(function ($q) {
                $q->where('p.stock', '<=', 0)
                    ->orWhereColumn('p.stock', '<=', 'p.stock_minimo');
            })
            ->when($estado === 'activos', function ($q) {
                $q->where('p.activo', true);
            })
            ->when($estado === 'inactivos', function ($q) {
                $q->where('p.activo', false);
            })
            ->orderByRaw('es_agotado DESC, p.stock ASC, p.nombre ASC');

        $productos = $query->paginate(30)->appends($request->query());

        $resumen = [
            'agotados' => DB::table('productos')->where('stock', '<=', 0)->count(),
            'escasos' => DB::table('productos')->where('stock', '>', 0)->whereColumn('stock', '<=', 'stock_minimo')->count(),
            'inactivos' => DB::table('productos')->where('activo', false)->count(),
            'rango' => [$fechaInicio, $fechaFin],
        ];

        return view('producto.reportes.escasos', compact('productos', 'resumen', 'estado'));
    }

    public function masVendidos(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolveRangoFechas($request);
        $estado = (string) $request->query('estado', 'todos');

        $query = $this->baseProductoMetricas($fechaInicio, $fechaFin)
            ->when($estado === 'activos', function ($q) {
                $q->where('p.activo', true);
            })
            ->when($estado === 'inactivos', function ($q) {
                $q->where('p.activo', false);
            })
            ->orderByDesc('cantidad_total')
            ->orderByDesc('ingreso_total');

        $productos = $query->paginate(30)->appends($request->query());

        $resumen = [
            'cantidad_total' => (int) $productos->sum('cantidad_total'),
            'ingreso_total' => (float) $productos->sum('ingreso_total'),
            'utilidad_total' => (float) $productos->sum('utilidad_total'),
        ];

        return view('producto.reportes.mas_vendidos', compact('productos', 'fechaInicio', 'fechaFin', 'estado', 'resumen'));
    }

    public function exportMasVendidosExcel(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolveRangoFechas($request);
        $estado = (string) $request->query('estado', 'todos');
        $filename = 'productos_mas_vendidos_' . $fechaInicio->format('Ymd') . '_' . $fechaFin->format('Ymd') . '.xlsx';

        return Excel::download(
            new ProductosMasVendidosExport($fechaInicio, $fechaFin, $estado),
            $filename
        );
    }

    public function masRentables(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolveRangoFechas($request);
        $estado = (string) $request->query('estado', 'todos');
        $orden = (string) $request->query('orden', 'utilidad_total');

        $metricas = $this->baseProductoMetricas($fechaInicio, $fechaFin)
            ->when($estado === 'activos', function ($q) {
                $q->where('p.activo', true);
            })
            ->when($estado === 'inactivos', function ($q) {
                $q->where('p.activo', false);
            });

        if ($orden === 'margen_asc') {
            $metricas->orderBy('margen_porcentual')->orderBy('utilidad_total');
        } elseif ($orden === 'ganancia_unitaria') {
            $metricas->orderByDesc('ganancia_unitaria')->orderByDesc('utilidad_total');
        } else {
            $metricas->orderByDesc('utilidad_total')->orderByDesc('margen_porcentual');
        }

        $productos = $metricas->paginate(30)->appends($request->query());

        $insights = [
            'top_utilidad' => $this->baseProductoMetricas($fechaInicio, $fechaFin)->orderByDesc('utilidad_total')->first(),
            'peor_margen' => $this->baseProductoMetricas($fechaInicio, $fechaFin)->orderBy('margen_porcentual')->first(),
            'alto_mov_baja_utilidad' => $this->baseProductoMetricas($fechaInicio, $fechaFin)
                ->orderByDesc('cantidad_total')
                ->havingRaw('utilidad_total <= 0 OR margen_porcentual < 10')
                ->first(),
        ];

        $alertas = [
            'costo_cero' => DB::table('productos')
                ->where('activo', true)
                ->where('costo', '<=', 0)
                ->count(),
            'stock_critico' => DB::table('productos')
                ->where('activo', true)
                ->whereColumn('stock', '<=', 'stock_minimo')
                ->count(),
            'margen_negativo' => (clone $this->baseProductoMetricas($fechaInicio, $fechaFin))
                ->havingRaw('margen_porcentual < 0')
                ->get()
                ->count(),
            'margen_bajo' => (clone $this->baseProductoMetricas($fechaInicio, $fechaFin))
                ->havingRaw('margen_porcentual >= 0 AND margen_porcentual < 10')
                ->get()
                ->count(),
        ];

        return view('producto.reportes.mas_rentables', compact('productos', 'fechaInicio', 'fechaFin', 'estado', 'orden', 'insights', 'alertas'));
    }

    public function exportMasRentablesPdf(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolveRangoFechas($request);
        $estado = (string) $request->query('estado', 'todos');

        $productos = $this->baseProductoMetricas($fechaInicio, $fechaFin)
            ->when($estado === 'activos', function ($q) {
                $q->where('p.activo', true);
            })
            ->when($estado === 'inactivos', function ($q) {
                $q->where('p.activo', false);
            })
            ->orderByDesc('utilidad_total')
            ->get();

        $pdf = PDF::loadView('producto.reportes.pdf.mas_rentables_pdf', [
            'productos' => $productos,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
        ])->setPaper('a4', 'landscape');

        $filename = 'productos_mas_rentables_' . $fechaInicio->format('Ymd') . '_' . $fechaFin->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    public function graficos(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolveRangoFechas($request);

        $base = $this->baseProductoMetricas($fechaInicio, $fechaFin);

        $topCantidad = (clone $base)->orderByDesc('cantidad_total')->limit(10)->get();
        $topIngresos = (clone $base)->orderByDesc('ingreso_total')->limit(10)->get();
        $topUtilidad = (clone $base)->orderByDesc('utilidad_total')->limit(10)->get();
        $menosRentables = (clone $base)->orderBy('margen_porcentual')->limit(10)->get();

        $stockActual = DB::table('productos')
            ->select(['nombre', 'stock', 'stock_minimo'])
            ->orderBy('stock')
            ->limit(15)
            ->get();

        return view('producto.reportes.graficos', compact(
            'fechaInicio',
            'fechaFin',
            'topCantidad',
            'topIngresos',
            'topUtilidad',
            'menosRentables',
            'stockActual'
        ));
    }

    public function usuariosVentas(Request $request)
    {
        [$fechaInicio, $fechaFin] = $this->resolveRangoFechas($request);
        $costoExpr = $this->costoUnitarioAplicadoExpr('dv', 'p');

        $base = DB::table('ventas as v')
            ->join('userables as uv', function ($join) {
                $join->on('uv.userable_id', '=', 'v.id')
                    ->where('uv.userable_type', '=', 'App\\Models\\Venta');
            })
            ->join('users as u', 'u.id', '=', 'uv.user_id')
            ->join('detalle_ventas as dv', 'dv.venta_id', '=', 'v.id')
            ->join('productos as p', 'p.id', '=', 'dv.producto_id')
            ->whereBetween('v.created_at', [$fechaInicio, $fechaFin]);

        $ventasPorUsuario = (clone $base)
            ->groupBy('u.id', 'u.name')
            ->select([
                'u.id as user_id',
                'u.name as usuario',
                DB::raw('COUNT(DISTINCT v.id) as cantidad_ventas'),
                DB::raw('SUM(dv.cantidad) as productos_vendidos'),
                DB::raw('SUM(dv.subtotal) as total_vendido'),
                DB::raw("SUM(dv.cantidad * {$costoExpr}) as costo_total"),
                DB::raw("SUM(dv.subtotal) - SUM(dv.cantidad * {$costoExpr}) as utilidad_total"),
                DB::raw('CASE WHEN COUNT(DISTINCT v.id) > 0 THEN SUM(dv.subtotal) / COUNT(DISTINCT v.id) ELSE 0 END as ticket_promedio'),
                DB::raw("CASE WHEN COUNT(DISTINCT v.id) > 0 THEN (SUM(dv.subtotal) - SUM(dv.cantidad * {$costoExpr})) / COUNT(DISTINCT v.id) ELSE 0 END as utilidad_promedio_venta"),
            ])
            ->orderByDesc('utilidad_total')
            ->get();

        $productosUsuario = (clone $base)
            ->groupBy('u.id', 'p.id', 'p.nombre')
            ->select([
                'u.id as user_id',
                'p.nombre as producto',
                DB::raw('SUM(dv.cantidad) as cantidad_total'),
            ])
            ->orderByDesc('cantidad_total')
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return optional($items->sortByDesc('cantidad_total')->first())->producto;
            });

        $ventasPorUsuario = $ventasPorUsuario->map(function ($fila) use ($productosUsuario) {
            $fila->producto_top = $productosUsuario->get($fila->user_id);
            return $fila;
        });

        return view('producto.reportes.usuarios_ventas', compact('ventasPorUsuario', 'fechaInicio', 'fechaFin'));
    }

    private function baseProductoMetricas(Carbon $fechaInicio, Carbon $fechaFin)
    {
        $costoExpr = $this->costoUnitarioAplicadoExpr('dv', 'p');

        return DB::table('detalle_ventas as dv')
            ->join('ventas as v', 'v.id', '=', 'dv.venta_id')
            ->join('productos as p', 'p.id', '=', 'dv.producto_id')
            ->whereBetween('v.created_at', [$fechaInicio, $fechaFin])
            ->groupBy('p.id', 'p.nombre', 'p.codigo', 'p.activo', 'p.stock', 'p.stock_minimo', 'p.costo', 'p.precio')
            ->select([
                'p.id',
                'p.nombre',
                'p.codigo',
                'p.activo',
                'p.stock',
                'p.stock_minimo',
                'p.costo',
                'p.precio',
                DB::raw('SUM(dv.cantidad) as cantidad_total'),
                DB::raw('SUM(dv.subtotal) as ingreso_total'),
                DB::raw("SUM(dv.cantidad * {$costoExpr}) as costo_total"),
                DB::raw("SUM(dv.subtotal) - SUM(dv.cantidad * {$costoExpr}) as utilidad_total"),
                DB::raw("AVG(dv.precio_unitario - {$costoExpr}) as ganancia_unitaria"),
                DB::raw("CASE WHEN SUM(dv.subtotal) > 0 THEN ((SUM(dv.subtotal) - SUM(dv.cantidad * {$costoExpr})) / SUM(dv.subtotal)) * 100 ELSE 0 END as margen_porcentual"),
            ]);
    }

    private function costoUnitarioAplicadoExpr(string $detalleAlias = 'dv', string $productoAlias = 'p'): string
    {
        return "(CASE WHEN {$detalleAlias}.costo_unitario IS NOT NULL AND {$detalleAlias}.costo_unitario > 0 THEN {$detalleAlias}.costo_unitario ELSE COALESCE({$productoAlias}.costo, 0) END)";
    }

    private function resolveRangoFechas(Request $request)
    {
        $fechaInicio = $request->filled('fecha_inicio')
            ? Carbon::parse($request->query('fecha_inicio'))->startOfDay()
            : Carbon::today()->startOfMonth();

        $fechaFin = $request->filled('fecha_fin')
            ? Carbon::parse($request->query('fecha_fin'))->endOfDay()
            : Carbon::today()->endOfDay();

        if ($fechaInicio->gt($fechaFin)) {
            $tmp = $fechaInicio->copy();
            $fechaInicio = $fechaFin->copy()->startOfDay();
            $fechaFin = $tmp->copy()->endOfDay();
        }

        return [$fechaInicio, $fechaFin];
    }
}
