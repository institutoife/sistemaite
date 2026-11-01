<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Support\SedeDB as DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductosMasVendidosExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    private $fechaInicio;
    private $fechaFin;
    private $estado;

    public function __construct(Carbon $fechaInicio, Carbon $fechaFin, $estado = 'todos')
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->estado = (string) $estado;
    }

    public function headings(): array
    {
        return [
            'Producto',
            'Codigo',
            'Estado',
            'Cantidad vendida',
            'Ingreso total',
            'Costo total',
            'Utilidad total',
            'Margen %',
        ];
    }

    public function collection()
    {
        $costoExpr = "(CASE WHEN dv.costo_unitario IS NOT NULL AND dv.costo_unitario > 0 THEN dv.costo_unitario ELSE COALESCE(p.costo, 0) END)";

        $query = DB::table('detalle_ventas as dv')
            ->join('ventas as v', 'v.id', '=', 'dv.venta_id')
            ->join('productos as p', 'p.id', '=', 'dv.producto_id')
            ->whereBetween('v.created_at', [$this->fechaInicio, $this->fechaFin])
            ->groupBy('p.id', 'p.nombre', 'p.codigo', 'p.activo')
            ->select([
                'p.nombre',
                'p.codigo',
                'p.activo',
                DB::raw('SUM(dv.cantidad) as cantidad_total'),
                DB::raw('SUM(dv.subtotal) as ingreso_total'),
                DB::raw("SUM(dv.cantidad * {$costoExpr}) as costo_total"),
                DB::raw("SUM(dv.subtotal) - SUM(dv.cantidad * {$costoExpr}) as utilidad_total"),
                DB::raw("CASE WHEN SUM(dv.subtotal) > 0 THEN ((SUM(dv.subtotal) - SUM(dv.cantidad * {$costoExpr})) / SUM(dv.subtotal)) * 100 ELSE 0 END as margen_porcentual"),
            ])
            ->when($this->estado === 'activos', function ($q) {
                $q->where('p.activo', true);
            })
            ->when($this->estado === 'inactivos', function ($q) {
                $q->where('p.activo', false);
            })
            ->orderByDesc('cantidad_total')
            ->orderByDesc('ingreso_total')
            ->get();

        return $query->map(function ($fila) {
            return new Collection([
                $fila->nombre,
                $fila->codigo,
                $fila->activo ? 'Activo' : 'Inactivo',
                (float) $fila->cantidad_total,
                (float) $fila->ingreso_total,
                (float) $fila->costo_total,
                (float) $fila->utilidad_total,
                round((float) $fila->margen_porcentual, 2),
            ]);
        });
    }
}
