<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Detallefactura;
use App\Models\Proveedor;
use App\Models\Ingrediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;

class FacturaController extends Controller
{
    /**
     * Display the main receipts/invoices management view.
     */
    public function index()
    {
        return view('recibos');
    }

    public function getFacturasByProveedorPagination(Request $request)
    {
        $idproveedor = $request->input('idproveedor');
        $skip = $request->input('skip');
        $limit = $request->input('limit');

        $facturas = Factura::where('proveedor_id', $idproveedor)
            ->skip($skip)
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'facturas' => $facturas,
            'message' => 'Facturas obtenidas correctamente'
        ]);
    }

    /**
     * Get paginated receipts with search and filters (proveedor, ingrediente).
     */
    public function getFacturasPagination(Request $request)
    {
        try {
            $skip = $request->input('skip', 0);
            $limit = $request->input('limit', 6);
            $search = $request->input('search', '');
            $idproveedor = $request->input('idproveedor', '');
            $idingrediente = $request->input('idingrediente', '');

            $query = Factura::with(['proveedors', 'detallefacturas.ingredientes']);

            if (!empty($idproveedor)) {
                $query->where('proveedor_id', $idproveedor);
            }

            if (!empty($idingrediente)) {
                $query->whereHas('detallefacturas', function ($q) use ($idingrediente) {
                    $q->where('ingrediente_id', $idingrediente);
                });
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('proveedors', function ($qp) use ($search) {
                        $qp->where('nombre', 'like', '%' . $search . '%');
                    })
                        ->orWhere('idfactura', 'like', '%' . $search . '%')
                        ->orWhere('total', 'like', '%' . $search . '%')
                        ->orWhere('emicion', 'like', '%' . $search . '%')
                        ->orWhere('fechapago', 'like', '%' . $search . '%');
                });
            }

            $facturas = $query->orderBy('emicion', 'desc')
                ->orderBy('idfactura', 'desc')
                ->skip($skip)
                ->take($limit)
                ->get();

            return response()->json([
                'success' => true,
                'facturas' => $facturas,
                'message' => 'Facturas obtenidas correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'facturas' => [],
                'message' => 'Error al obtener facturas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Existing pagination by supplier ID (preserved for compatibility with proveedores view).
     */


    /**
     * Get summary statistics: total receipts, paid receipts, pending receipts.
     */
    public function getEstadisticas()
    {
        try {
            $total = Factura::count();
            $pagados = Factura::whereNotNull('fechapago')->where('fechapago', '!=', '')->count();
            $pendientes = Factura::where(function ($q) {
                $q->whereNull('fechapago')->orWhere('fechapago', '');
            })->count();

            return response()->json([
                'success' => true,
                'total' => $total,
                'pagados' => $pagados,
                'pendientes' => $pendientes,
                'message' => 'Estadísticas obtenidas correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'total' => 0,
                'pagados' => 0,
                'pendientes' => 0,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Monthly expense breakdown for the current year (for the chart modal).
     */
    public function getGastosMensuales(Request $request)
    {
        try {
            $year = $request->input('year', date('Y'));
            $gastos = DB::table('facturas')
                ->select(
                    DB::raw('MONTH(emicion) as mes'),
                    DB::raw('SUM(total) as total_gastado'),
                    DB::raw('COUNT(idfactura) as cantidad_facturas')
                )
                ->whereRaw('YEAR(emicion) = ?', [$year])
                ->groupBy(DB::raw('MONTH(emicion)'))
                ->orderBy('mes', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'gastos' => $gastos,
                'year' => $year,
                'message' => 'Gastos mensuales obtenidos correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'gastos' => [],
                'message' => 'Error al obtener gastos mensuales: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get complete detail of a single invoice and its items.
     */
    public function getDetalleFactura($id)
    {
        try {
            $factura = Factura::with(['proveedors', 'detallefacturas.ingredientes'])->find($id);
            if (!$factura) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'factura' => $factura,
                'message' => 'Detalle de factura obtenido correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener detalle: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new invoice with its detail items and physical receipt photo.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'emicion' => 'required|date',
            'proveedor_id' => 'required|exists:proveedors,idproveedor',
            'fechapago' => 'nullable|date',
            'total' => 'nullable|numeric|min:0',
            'foto' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg|max:5120',
        ], [
            'emicion.required' => 'La fecha de emisión es requerida',
            'emicion.date' => 'La fecha de emisión no es válida',
            'proveedor_id.required' => 'El proveedor es requerido',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe',
            'fechapago.date' => 'La fecha de pago no es válida',
            'foto.image' => 'El archivo de la factura debe ser una imagen válida',
            'foto.max' => 'La imagen no debe pesar más de 5MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Manejo de la foto física
            $fotoUrl = null;
            if ($request->hasFile('foto')) {
                $destinationPath = public_path('imagenes_facturas');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('foto');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $filename);
                $fotoUrl = '/imagenes_facturas/' . $filename;
            }

            // Procesar detalles de ingredientes
            $detallesRaw = $request->input('detalles');
            $detalles = [];
            if (is_string($detallesRaw)) {
                $detalles = json_decode($detallesRaw, true) ?: [];
            } elseif (is_array($detallesRaw)) {
                $detalles = $detallesRaw;
            }

            $totalCalculado = 0;
            foreach ($detalles as $item) {
                $cant = floatval($item['cantidad'] ?? 0);
                $val = floatval($item['valorunitario'] ?? 0);
                $totalCalculado += ($cant * $val);
            }

            $totalFinal = $totalCalculado > 0 ? $totalCalculado : floatval($request->input('total', 0));

            $factura = new Factura();
            $factura->emicion = $request->emicion;
            $factura->fechapago = !empty($request->fechapago) ? $request->fechapago : null;
            $factura->total = round($totalFinal, 2);
            $factura->foto = $fotoUrl;
            $factura->proveedor_id = $request->proveedor_id;
            $factura->save();

            // Guardar detalles
            foreach ($detalles as $item) {
                if (!empty($item['ingrediente_id']) && !empty($item['cantidad'])) {
                    $ingrediente = Ingrediente::where('idingrediente', $item['ingrediente_id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$ingrediente) {
                        throw ValidationException::withMessages([
                            'detalles' => 'Uno de los ingredientes seleccionados ya no existe.',
                        ]);
                    }

                    $medida = $ingrediente->medida;
                    $cantidadStockBase = $this->convertirAUnidadBase(
                        (float) $item['cantidad'],
                        $medida
                    );

                    Detallefactura::create([
                        'factura_id' => $factura->idfactura,
                        'ingrediente_id' => $item['ingrediente_id'],
                        'cantidad' => round((float) $item['cantidad'], 3),
                        'valorunitario' => floatval($item['valorunitario'] ?? 0),
                        'fechavencimiento' => !empty($item['fechavencimiento']) ? $item['fechavencimiento'] : Carbon::now()->addMonths(6)->format('Y-m-d'),
                    ]);

                    $ingrediente->stock += $cantidadStockBase;
                    $ingrediente->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'factura' => $factura->load(['proveedors', 'detallefacturas.ingredientes']),
                'message' => 'Recibo creado correctamente'
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'No se pudo convertir una unidad de ingrediente.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar recibo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing invoice (confirm payment, edit date, supplier, photo, etc.).
     */
    public function update(Request $request)
    {
        $id = $request->input('idfactura');
        $factura = Factura::find($id);

        if (!$factura) {
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'emicion' => 'required|date',
            'proveedor_id' => 'required|exists:proveedors,idproveedor',
            'fechapago' => 'nullable|date',
            'total' => 'nullable|numeric|min:0',
            'foto' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg|max:5120',
        ], [
            'emicion.required' => 'La fecha de emisión es requerida',
            'emicion.date' => 'La fecha de emisión no es válida',
            'proveedor_id.required' => 'El proveedor es requerido',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe',
            'fechapago.date' => 'La fecha de pago no es válida',
            'foto.image' => 'El archivo debe ser una imagen válida',
            'foto.max' => 'La imagen no debe pesar más de 5MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($request->hasFile('foto')) {
                $destinationPath = public_path('imagenes_facturas');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                $file = $request->file('foto');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $filename);
                $factura->foto = '/imagenes_facturas/' . $filename;
            }

            $factura->emicion = $request->emicion;
            $factura->fechapago = !empty($request->fechapago) ? $request->fechapago : null;
            $factura->proveedor_id = $request->proveedor_id;

            // Si se enviaron detalles nuevos/actualizados
            $detallesRaw = $request->input('detalles');
            if ($detallesRaw !== null) {
                $detalles = is_string($detallesRaw) ? (json_decode($detallesRaw, true) ?: []) : $detallesRaw;
                if (!empty($detalles)) {
                    $detallesAnteriores = Detallefactura::where('factura_id', $factura->idfactura)
                        ->get();
                    $variacionesStock = [];

                    foreach ($detallesAnteriores as $detalleAnterior) {
                        $idIngrediente = (int) $detalleAnterior->ingrediente_id;
                        $ingredienteAnterior = Ingrediente::find($idIngrediente);
                        if (!$ingredienteAnterior) {
                            continue;
                        }

                        $variacionesStock[$idIngrediente] = ($variacionesStock[$idIngrediente] ?? 0)
                            - $this->convertirAUnidadBase(
                                (float) $detalleAnterior->cantidad,
                                $ingredienteAnterior->medida
                            );
                    }

                    $totalCalculado = 0;
                    $detallesNuevos = [];
                    foreach ($detalles as $item) {
                        if (!empty($item['ingrediente_id']) && !empty($item['cantidad'])) {
                            $ingrediente = Ingrediente::find($item['ingrediente_id']);

                            if (!$ingrediente) {
                                throw ValidationException::withMessages([
                                    'detalles' => 'Uno de los ingredientes seleccionados ya no existe.',
                                ]);
                            }

                            $medida = $ingrediente->medida;
                            $variacionesStock[(int) $ingrediente->idingrediente] =
                                ($variacionesStock[(int) $ingrediente->idingrediente] ?? 0)
                                + $this->convertirAUnidadBase((float) $item['cantidad'], $medida);

                            $detallesNuevos[] = [
                                'factura_id' => $factura->idfactura,
                                'ingrediente_id' => $ingrediente->idingrediente,
                                'cantidad' => round((float) $item['cantidad'], 3),
                                'valorunitario' => floatval($item['valorunitario'] ?? 0),
                                'fechavencimiento' => !empty($item['fechavencimiento']) ? $item['fechavencimiento'] : Carbon::now()->addMonths(6)->format('Y-m-d'),
                            ];
                            $totalCalculado += (floatval($item['cantidad']) * floatval($item['valorunitario'] ?? 0));
                        }
                    }

                    foreach ($variacionesStock as $idIngrediente => $variacion) {
                        $ingrediente = Ingrediente::where('idingrediente', $idIngrediente)
                            ->lockForUpdate()
                            ->first();

                        if (!$ingrediente || $ingrediente->stock + $variacion < 0) {
                            throw ValidationException::withMessages([
                                'detalles' => 'No se puede reducir la compra porque parte de ese stock ya fue utilizado.',
                            ]);
                        }

                        $ingrediente->stock += $variacion;
                        $ingrediente->save();
                    }

                    Detallefactura::where('factura_id', $factura->idfactura)->delete();
                    foreach ($detallesNuevos as $detalleNuevo) {
                        Detallefactura::create($detalleNuevo);
                    }

                    if ($totalCalculado > 0) {
                        $factura->total = round($totalCalculado, 2);
                    }
                }
            } elseif ($request->filled('total')) {
                $factura->total = round(floatval($request->total), 2);
            }

            $factura->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'factura' => $factura->load(['proveedors', 'detallefacturas.ingredientes']),
                'message' => 'Recibo actualizado correctamente'
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el stock de ingredientes.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar recibo: ' . $e->getMessage()
            ], 500);
        }
    }

    private function convertirAUnidadBase(float $cantidad, string $medida): float
    {
        $unidad = mb_strtolower(trim($medida));
        $unidad = strtr($unidad, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
        ]);

        $factor = match ($unidad) {
            'mg', 'miligramo', 'miligramos' => 1 / 28349.523125,
            'g', 'gr', 'gramo', 'gramos' => 1 / 28.349523125,
            'kg', 'kilo', 'kilos', 'kilogramo', 'kilogramos' => 1000 / 28.349523125,
            'oz', 'onza', 'onzas' => 1,
            'lb', 'lbs', 'libra', 'libras' => 16,
            't', 'ton', 'tonelada', 'toneladas' => 1000000 / 28.349523125,
            'ml', 'mililitro', 'mililitros', 'l', 'litro', 'litros' => 1,
            'u', 'un', 'unidad', 'unidades', 'pieza', 'piezas',
            'huevo', 'huevos', 'lata', 'latas', 'barra', 'barras' => 1,
            default => null,
        };

        if ($factor === null) {
            throw ValidationException::withMessages([
                'detalles' => "La unidad '{$medida}' no tiene una conversión configurada para el inventario.",
            ]);
        }

        return round($cantidad * $factor, 3);
    }

    /**
     * Generate PDF report of monthly expenses and most purchased ingredients.
     */
    public function generatePDF()
    {
        try {
            $currentYear = date('Y');

            // Gastos por mes del año actual
            $gastosPorMes = DB::table('facturas')
                ->select(
                    DB::raw('MONTH(emicion) as mes'),
                    DB::raw('COUNT(idfactura) as cantidad_facturas'),
                    DB::raw('SUM(total) as total_gastado')
                )
                ->whereRaw('YEAR(emicion) = ?', [$currentYear])
                ->groupBy(DB::raw('MONTH(emicion)'))
                ->orderBy('mes', 'asc')
                ->get();

            // Mapear nombres de meses
            $mesesNombres = [
                1 => 'Enero',
                2 => 'Febrero',
                3 => 'Marzo',
                4 => 'Abril',
                5 => 'Mayo',
                6 => 'Junio',
                7 => 'Julio',
                8 => 'Agosto',
                9 => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre'
            ];
            foreach ($gastosPorMes as $gasto) {
                $gasto->nombre_mes = $mesesNombres[$gasto->mes] ?? 'Mes ' . $gasto->mes;
            }

            // Ingrediente más comprado
            $ingredienteMasComprado = DB::table('ingredientes')
                ->join('detallefacturas', 'ingredientes.idingrediente', '=', 'detallefacturas.ingrediente_id')
                ->select(
                    'ingredientes.nombre',
                    'ingredientes.medida',
                    DB::raw('SUM(detallefacturas.cantidad) as total_cantidad'),
                    DB::raw('SUM(detallefacturas.cantidad * detallefacturas.valorunitario) as gasto_total')
                )
                ->groupBy('ingredientes.idingrediente', 'ingredientes.nombre', 'ingredientes.medida')
                ->orderBy('total_cantidad', 'desc')
                ->first();

            // Ranking top 5 ingredientes más comprados
            $topIngredientes = DB::table('ingredientes')
                ->join('detallefacturas', 'ingredientes.idingrediente', '=', 'detallefacturas.ingrediente_id')
                ->select(
                    'ingredientes.nombre',
                    'ingredientes.medida',
                    DB::raw('SUM(detallefacturas.cantidad) as total_cantidad'),
                    DB::raw('SUM(detallefacturas.cantidad * detallefacturas.valorunitario) as gasto_total')
                )
                ->groupBy('ingredientes.idingrediente', 'ingredientes.nombre', 'ingredientes.medida')
                ->orderBy('total_cantidad', 'desc')
                ->take(5)
                ->get();

            // Estadísticas generales
            $totalRecibos = Factura::count();
            $totalPagados = Factura::whereNotNull('fechapago')->where('fechapago', '!=', '')->count();
            $totalPendientes = Factura::where(function ($q) {
                $q->whereNull('fechapago')->orWhere('fechapago', '');
            })->count();
            $sumaTotalGastos = Factura::sum('total');

            // Listado de facturas recientes
            $facturas = Factura::with('proveedors')->orderBy('emicion', 'desc')->take(50)->get();

            $pdf = Pdf::loadView('reports.recibosReport', compact(
                'gastosPorMes',
                'ingredienteMasComprado',
                'topIngredientes',
                'totalRecibos',
                'totalPagados',
                'totalPendientes',
                'sumaTotalGastos',
                'facturas',
                'currentYear'
            ));

            return $pdf->download('reporte_recibos_' . date('Y_m_d') . '.pdf');
        } catch (Exception $e) {
            return back()->with('error', 'Error al generar reporte: ' . $e->getMessage());
        }
    }
}
