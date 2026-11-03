<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /*$proveedores = Proveedor::all();
        return view('proveedores', compact('proveedores'));*/
        return view('proveedores');
    }

    public function getProveedoresPagination(Request $request)
    {
        try {
            if (isset($request->skip)) {
                $skip = $request->skip;
            } else {
                $skip = 0;
            }
            if (isset($request->limit)) {
                $limit = $request->limit;
            } else {
                $limit = 6;
            }
            $proveedores = Proveedor::skip($skip)->take($limit)->get();
            return response()->json([
                'proveedores' => $proveedores,
                'success' => true,
                'message' => 'Proveedores obtenidos correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {

        $name = $request->nameNewSupplier;
        $phone = $request->phoneNewSupplier;
        $email = $request->emailNewSupplier;
        $description = $request->descriptionNewSupplier == '' ? 'Sin descripción' : $request->descriptionNewSupplier;

        $validator = Validator::make($request->all(), [
            'nameNewSupplier' => 'required|string|min:3|max:60',
            'phoneNewSupplier' => 'required|regex:/^[567][0-9]{3}-[0-9]{4}$/',
            'emailNewSupplier' => 'required|email|max:255',
        ], [
            'nameNewSupplier.required' => 'El nombre es requerido',
            'nameNewSupplier.min' => 'El nombre debe tener al menos 3 caracteres',
            'nameNewSupplier.max' => 'El nombre debe tener menos de 60 caracteres',
            'phoneNewSupplier.required' => 'El telefono es requerido',
            'phoneNewSupplier.regex' => 'El teléfono debe iniciar con 5, 6 o 7 y tener el formato 7000-0000.',
            'phoneNewSupplier.min' => 'El telefono debe tener al menos 7 caracteres',
            'phoneNewSupplier.max' => 'El telefono debe tener menos de 20 caracteres',
            'emailNewSupplier.required' => 'El correo es requerido',
            'emailNewSupplier.email' => 'El correo debe ser un correo valido',
            'emailNewSupplier.max' => 'El correo debe tener menos de 255 caracteres',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear proveedor',
                'errors' => $validator->errors()
            ]);
        }

        $proveedor = new Proveedor();
        $proveedor->nombre = $name;
        $proveedor->telefono = $phone;
        $proveedor->correo = $email;
        $proveedor->descripcion = $description;
        $proveedor->save();



        return response()->json([
            'proveedor' => $proveedor,
            'success' => true,
            'message' => 'Proveedor creado correctamente'
        ]);
    }

    public function searchProveedor(Request $request)
    {
        $search = $request->search;
        $filtroIngrediente = $request->filtroIngrediente;
        $filtroCosto = $request->filtroCosto;

        $proveedores = null;
        try {
            if ($filtroIngrediente == "") {
                $proveedores = Proveedor::where('nombre', 'like', '%' . $search . '%')->orWhere('telefono', 'like', '%' . $search . '%')->orWhere('correo', 'like', '%' . $search . '%')->take(10)->get();
            } else if ($filtroIngrediente != "" and $filtroCosto == "") {
                $proveedores = DB::table('proveedors')
                    ->join('facturas', 'proveedors.idproveedor', '=', 'facturas.proveedor_id')
                    ->join('detallefacturas', 'facturas.idfactura', '=', 'detallefacturas.factura_id')
                    ->join('ingredientes', 'detallefacturas.ingrediente_id', '=', 'ingredientes.idingrediente')
                    ->select(
                        'proveedors.idproveedor',
                        'proveedors.nombre',
                        'proveedors.telefono',
                        'proveedors.correo',
                        'proveedors.descripcion'
                    )
                    ->where('ingredientes.idingrediente', $filtroIngrediente)
                    ->where('proveedors.nombre', 'like', '%' . $search . '%')
                    ->groupBy(
                        'proveedors.idproveedor',
                        'proveedors.nombre',
                        'proveedors.telefono',
                        'proveedors.correo',
                        'proveedors.descripcion'
                    )
                    ->take(10)
                    ->get();
            } else if ($filtroIngrediente != "" and $filtroCosto != "") {
                $proveedores = DB::table('proveedors')
                    ->join('facturas', 'proveedors.idproveedor', '=', 'facturas.proveedor_id')
                    ->join('detallefacturas', 'facturas.idfactura', '=', 'detallefacturas.factura_id')
                    ->join('ingredientes', 'detallefacturas.ingrediente_id', '=', 'ingredientes.idingrediente')
                    ->select(
                        'proveedors.idproveedor',
                        'proveedors.nombre',
                        'proveedors.telefono',
                        'proveedors.correo',
                        'proveedors.descripcion'
                    )
                    ->whereBetween('facturas.emicion', [Carbon::now()->subDays(30)->format('Y-m-d'), Carbon::now()->format('Y-m-d')])
                    ->where('ingredientes.idingrediente', $filtroIngrediente)
                    ->where('proveedors.nombre', 'like', '%' . $search . '%')
                    ->groupBy(
                        'proveedors.idproveedor',
                        'proveedors.nombre',
                        'proveedors.telefono',
                        'proveedors.correo',
                        'proveedors.descripcion'
                    )
                    ->orderBy('facturas.total', $filtroCosto == 'barato' ? 'asc' : 'desc')
                    ->take(10)
                    ->get();
            } else {
                return response()->json([
                    'proveedores' => $proveedores,
                    'success' => false,
                    'message' => 'No se encontraron proveedores'
                ]);
            }

            return response()->json([
                'proveedores' => $proveedores,
                'success' => true,
                'message' => 'Proveedores obtenidos correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'proveedores' => $proveedores,
                'success' => false,
                'message' => 'Error al obtener proveedores'
            ]);
        }
    }


    public function graphiGastosEnCompras(Request $request)
    {
        try {
            $idProveedor = $request->idProveedor;
            $gastosEnCompras = DB::table('facturas as f')
                ->join('proveedors as p', 'f.proveedor_id', '=', 'p.idproveedor')
                ->select(
                    DB::raw('MONTH(f.emicion) as mes'),
                    DB::raw('SUM(f.total) as total_comprado')
                )
                ->where('f.proveedor_id', $idProveedor)
                ->whereRaw('YEAR(f.emicion) = YEAR(CURDATE())')
                ->groupBy(DB::raw('MONTH(f.emicion)'))
                ->orderBy('mes', 'asc')
                ->get();

            return response()->json([
                'gastosEnCompras' => $gastosEnCompras,
                'success' => true,
                'message' => 'Gastos en compras obtenidos correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'gastosEnCompras' => null,
                'success' => false,
                'message' => 'Error al obtener gastos en compras'
            ]);
        }
    }

    public function generatePDF()
    {
        $proveedores = Proveedor::all();
        $respuestas = [];
        foreach ($proveedores as $proveedor) {

            $mesNombreSql = "
    CASE MONTH(f.emicion)
        WHEN 1 THEN 'Enero'
        WHEN 2 THEN 'Febrero'
        WHEN 3 THEN 'Marzo'
        WHEN 4 THEN 'Abril'
        WHEN 5 THEN 'Mayo'
        WHEN 6 THEN 'Junio'
        WHEN 7 THEN 'Julio'
        WHEN 8 THEN 'Agosto'
        WHEN 9 THEN 'Septiembre'
        WHEN 10 THEN 'Octubre'
        WHEN 11 THEN 'Noviembre'
        WHEN 12 THEN 'Diciembre'
    END
";

            $reporteFacturas = DB::table('facturas as f')
                ->select([
                    DB::raw('YEAR(f.emicion) as anio'),
                    DB::raw($mesNombreSql . ' as mes_nombre'),
                    DB::raw('COUNT(f.idfactura) as facturas_emitidas'),
                    DB::raw('SUM(CASE WHEN f.fechapago IS NOT NULL THEN f.total ELSE 0 END) as total_pagado'),
                    DB::raw('SUM(CASE WHEN f.fechapago IS NULL THEN f.total ELSE 0 END) as total_no_pagado')
                ])
                ->where('f.proveedor_id', $proveedor->idproveedor)
                ->groupBy([
                    DB::raw('YEAR(f.emicion)'),
                    DB::raw('MONTH(f.emicion)'),
                    DB::raw($mesNombreSql)
                ])
                ->orderBy('anio', 'asc')
                ->orderBy(DB::raw('MONTH(f.emicion)'), 'asc')
                ->get();

            $respuestas[] = [
                'proveedor' => $proveedor,
                'reporteFacturas' => $reporteFacturas
            ];
        }

        $pdf = PDF::loadView('reports.proveedoresReport', compact('respuestas'));
        return $pdf->download('proveedoresReport.pdf');

        //return view('reports.proveedoresReport', compact('respuestas'));
    }

    public function updateScore(Request $request)
    {
        try {
            $idProveedor = $request->idproveedor;
            $puntuacion = $request->puntuacion;
            $proveedor = Proveedor::find($idProveedor);
            $proveedor->puntuacion = $puntuacion;
            $proveedor->save();

            return response()->json([
                'proveedor' => $proveedor,
                'success' => true,
                'message' => 'Puntuacion actualizada correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'proveedor' => null,
                'success' => false,
                'message' => 'Error al actualizar puntuacion'
            ]);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Proveedor $proveedor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Proveedor $proveedor)
    {
        //
    }

    public function proveedroMenosSolicitado(Request $request)
    {
        $proveedor = DB::table('proveedors')
            ->join('facturas', 'proveedors.idproveedor', '=', 'facturas.proveedor_id')
            ->join('detallefacturas', 'facturas.idfactura', '=', 'detallefacturas.factura_id')
            ->select(
                'proveedors.idproveedor',
                'proveedors.nombre',
                DB::raw('SUM(detallefacturas.cantidad) as total_comprado')
            )
            ->whereBetween('facturas.emicion', [Carbon::now()->subDays(30)->format('Y-m-d'), Carbon::now()->format('Y-m-d')])
            ->groupBy('proveedors.idproveedor', 'proveedors.nombre')
            ->orderBy('total_comprado', 'asc')
            ->first();

        return response()->json([
            'proveedor' => $proveedor,
            'success' => true,
            'message' => 'Proveedor menos solicitado'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateProveedor(Request $request)
    {
        $name = $request->nameNewSupplier;
        $phone = $request->phoneNewSupplier;
        $email = $request->emailNewSupplier;
        $description = $request->descriptionNewSupplier == '' ? 'Sin descripción' : $request->descriptionNewSupplier;

        $validator = Validator::make($request->all(), [
            'nameNewSupplier' => 'required|string|min:3|max:60',
            'phoneNewSupplier' => 'required|regex:/^[567][0-9]{3}-[0-9]{4}$/',
            'emailNewSupplier' => 'required|email|max:255',
        ], [
            'nameNewSupplier.required' => 'El nombre es requerido',
            'nameNewSupplier.min' => 'El nombre debe tener al menos 3 caracteres',
            'nameNewSupplier.max' => 'El nombre debe tener menos de 60 caracteres',
            'phoneNewSupplier.required' => 'El telefono es requerido',
            'phoneNewSupplier.regex' => 'El teléfono debe iniciar con 5, 6 o 7 y tener el formato 7000-0000.',
            'phoneNewSupplier.min' => 'El telefono debe tener al menos 7 caracteres',
            'phoneNewSupplier.max' => 'El telefono debe tener menos de 20 caracteres',
            'emailNewSupplier.required' => 'El correo es requerido',
            'emailNewSupplier.email' => 'El correo debe ser un correo valido',
            'emailNewSupplier.max' => 'El correo debe tener menos de 255 caracteres',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar proveedor',
                'errors' => $validator->errors()
            ]);
        }

        $proveedor = Proveedor::find($request->idNewSupplier);
        $proveedor->nombre = $name;
        $proveedor->telefono = $phone;
        $proveedor->correo = $email;
        $proveedor->descripcion = $description;
        $proveedor->save();

        return response()->json([
            'proveedor' => $proveedor,
            'success' => true,
            'message' => 'Proveedor actualizado correctamente'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proveedor $proveedor) {}
}
