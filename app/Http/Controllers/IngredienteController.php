<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class IngredienteController extends Controller
{
    /**
     * Muestra la vista de gestión de ingredientes.
     */
    public function index()
    {
        return view('ingredientes');
    }

    // ──────────────────────────────────────────────────────────
    //  LISTADO PAGINADO
    // ──────────────────────────────────────────────────────────

    /**
     * Devuelve ingredientes con paginacion (skip/limit).
     * POST /api/ingredientes/getIngredientesPagination
     */
    public function getIngredientesPagination(Request $request)
    {
        try {
            $skip  = $request->skip  ?? 0;
            $limit = $request->limit ?? 10;

            $ingredientes = Ingrediente::skip($skip)->take($limit)->get();
            $total        = Ingrediente::count();

            return response()->json([
                'ingredientes' => $ingredientes,
                'total'        => $total,
                'success'      => true,
                'message'      => 'Ingredientes obtenidos correctamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'ingredientes' => [],
                'total'        => 0,
                'success'      => false,
                'message'      => 'Error al obtener ingredientes',
                'error'        => $e->getMessage(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────
    //  BUSQUEDA
    // ──────────────────────────────────────────────────────────

    /**
     * Busca ingredientes por nombre o descripcion.
     * POST /api/ingredientes/searchIngrediente
     */
    public function searchIngrediente(Request $request)
    {
        try {
            $search = $request->search ?? '';

            $ingredientes = Ingrediente::where('nombre', 'like', '%' . $search . '%')
                ->orWhere('descripcion', 'like', '%' . $search . '%')
                ->take(20)
                ->get();

            return response()->json([
                'ingredientes' => $ingredientes,
                'success'      => true,
                'message'      => 'Busqueda completada',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'ingredientes' => [],
                'success'      => false,
                'message'      => 'Error en la busqueda',
                'error'        => $e->getMessage(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────
    //  ESTADISTICAS
    // ──────────────────────────────────────────────────────────

    /**
     * Devuelve estadisticas: total, stock bajo y proximos a vencer.
     * GET /api/ingredientes/estadisticas
     */
    public function estadisticas(Request $request)
    {
        try {
            $umbral = (int) ($request->umbral ?? 10);
            $dias   = (int) ($request->dias   ?? 30);

            $total     = Ingrediente::count();
            $stockBajo = Ingrediente::where('stock', '<=', $umbral)->count();

            $proximosVencer = DB::table('detallefacturas as df')
                ->join('ingredientes as i', 'df.ingrediente_id', '=', 'i.idingrediente')
                ->whereBetween('df.fechavencimiento', [
                    Carbon::today()->format('Y-m-d'),
                    Carbon::today()->addDays($dias)->format('Y-m-d'),
                ])
                ->distinct('df.iddetallefactura')
                ->count('df.iddetallefactura');

            return response()->json([
                'total'           => $total,
                'stock_bajo'      => $stockBajo,
                'proximos_vencer' => $proximosVencer,
                'success'         => true,
                'message'         => 'Estadisticas obtenidas correctamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadisticas',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────
    //  PROXIMOS A VENCER
    // ──────────────────────────────────────────────────────────

    /**
     * Lista los lotes de detallefacturas cuya fechavencimiento esta
     * dentro de los proximos N dias.
     * "cantidad" = unidades de ese ingrediente en ese recibo.
     * GET /api/ingredientes/proximosVencer?dias=30
     */
    public function proximosVencer(Request $request)
    {
        try {
            $dias = $request->dias ?? 30;

            $lotes = DB::table('detallefacturas as df')
                ->join('ingredientes as i', 'df.ingrediente_id', '=', 'i.idingrediente')
                ->select(
                    'df.iddetallefactura',
                    'i.idingrediente',
                    'i.nombre as nombre_ingrediente',
                    'df.cantidad',
                    'df.valorunitario',
                    'df.fechavencimiento'
                )
                ->whereBetween('df.fechavencimiento', [
                    Carbon::today()->format('Y-m-d'),
                    Carbon::today()->addDays((int) $dias)->format('Y-m-d'),
                ])
                ->orderBy('df.fechavencimiento', 'asc')
                ->get();

            return response()->json([
                'lotes'   => $lotes,
                'success' => true,
                'message' => 'Lotes proximos a vencer obtenidos correctamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'lotes'   => [],
                'success' => false,
                'message' => 'Error al obtener lotes proximos a vencer',
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────
    //  CREAR INGREDIENTE
    // ──────────────────────────────────────────────────────────

    /**
     * Crea un nuevo ingrediente.
     * POST /api/ingredientes/crearIngrediente
     */
    public function crearIngrediente(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre'      => 'required|string|min:2|max:30',
            'medida'      => 'required|in:g,kg,lb,oz,u,L,ml',
            'stock'       => 'required|numeric|min:0',
            'descripcion' => 'nullable|string|max:1000',
        ], [
            'nombre.required' => 'El nombre del ingrediente es obligatorio.',
            'nombre.min'      => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max'      => 'El nombre no puede superar los 30 caracteres.',
            'medida.required' => 'La medida es obligatoria.',
            'medida.in'       => 'Seleccione una medida válida.',
            'stock.required'  => 'El stock es obligatorio.',
            'stock.numeric'   => 'El stock debe ser un número válido.',
            'stock.min'       => 'El stock no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validacion',
                'errors'  => $validator->errors(),
            ]);
        }

        try {
            $ingrediente              = new Ingrediente();
            $ingrediente->nombre      = $request->nombre;
            $ingrediente->stock       = $request->stock;
            $ingrediente->descripcion = $request->descripcion ?? '';
            $ingrediente->medida      = $request->medida;
            $ingrediente->save();

            return response()->json([
                'ingrediente' => $ingrediente,
                'success'     => true,
                'message'     => 'Ingrediente creado correctamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'ingrediente' => null,
                'success'     => false,
                'message'     => 'Error al crear el ingrediente',
                'error'       => $e->getMessage(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────
    //  ACTUALIZAR INGREDIENTE
    // ──────────────────────────────────────────────────────────

    /**
     * Actualiza un ingrediente existente.
     * POST /api/ingredientes/actualizarIngrediente
     */
    public function actualizarIngrediente(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idingrediente' => 'required|integer|exists:ingredientes,idingrediente',
            'nombre'        => 'required|string|min:2|max:30',
            'medida'        => 'required|in:g,kg,lb,oz,u,L,ml',
            'stock'         => 'required|numeric|min:0',
            'descripcion'   => 'nullable|string|max:1000',
        ], [
            'idingrediente.required' => 'El ID del ingrediente es obligatorio.',
            'idingrediente.exists'   => 'El ingrediente no existe.',
            'nombre.required'        => 'El nombre del ingrediente es obligatorio.',
            'nombre.min'             => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max'             => 'El nombre no puede superar los 30 caracteres.',
            'medida.required'        => 'La medida es obligatoria.',
            'medida.in'              => 'Seleccione una medida válida.',
            'stock.required'         => 'El stock es obligatorio.',
            'stock.numeric'          => 'El stock debe ser un número válido.',
            'stock.min'              => 'El stock no puede ser negativo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validacion',
                'errors'  => $validator->errors(),
            ]);
        }

        try {
            $ingrediente              = Ingrediente::find($request->idingrediente);
            $ingrediente->nombre      = $request->nombre;
            $ingrediente->stock       = $request->stock;
            $ingrediente->descripcion = $request->descripcion ?? '';
            $ingrediente->medida      = $request->medida;
            $ingrediente->save();

            return response()->json([
                'ingrediente' => $ingrediente,
                'success'     => true,
                'message'     => 'Ingrediente actualizado correctamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'ingrediente' => null,
                'success'     => false,
                'message'     => 'Error al actualizar el ingrediente',
                'error'       => $e->getMessage(),
            ]);
        }
    }

    // ──────────────────────────────────────────────────────────
    //  METODOS HEREDADOS (ya existentes)
    // ──────────────────────────────────────────────────────────

    public function allIngredients(Request $request)
    {
        try {
            $ingredientes = Ingrediente::all();
            return response()->json([
                'ingredientes' => $ingredientes,
                'success'      => true,
                'message'      => 'Ingredientes obtenidos correctamente',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'ingredientes' => null,
                'success'      => false,
                'message'      => 'Error al obtener los ingredientes',
                'error'        => $e->getMessage(),
            ]);
        }
    }

    public function ingredienteMasComprado(Request $request)
    {
        try {
            $Ingrediente = DB::table('ingredientes')
                ->join('detallefacturas', 'ingredientes.idingrediente', '=', 'detallefacturas.ingrediente_id')
                ->join('facturas', 'detallefacturas.factura_id', '=', 'facturas.idfactura')
                ->select(
                    'ingredientes.idingrediente',
                    'ingredientes.nombre',
                    DB::raw('SUM(detallefacturas.cantidad) as total_comprado')
                )
                ->whereBetween('detallefacturas.emicion', [Carbon::now()->subDays(30)->format('Y-m-d'), Carbon::now()->format('Y-m-d')])
                ->groupBy('ingredientes.idingrediente', 'ingredientes.nombre')
                ->orderBy('total_comprado', 'desc')
                ->first();
        } catch (Exception $e) {
            return response()->json([
                'ingrediente' => null,
                'success'     => false,
                'message'     => 'Error al obtener el ingrediente mas comprado',
                'error'       => $e->getMessage(),
            ]);
        }

        return response()->json([
            'ingrediente' => $Ingrediente,
            'success'     => true,
            'message'     => 'Ingrediente mas comprado',
        ]);
    }

    public function ingredienteMenosComprado(Request $request)
    {
        try {
            $Ingrediente = DB::table('ingredientes')
                ->join('detallefacturas', 'ingredientes.idingrediente', '=', 'detallefacturas.ingrediente_id')
                ->join('facturas', 'detallefacturas.factura_id', '=', 'facturas.idfactura')
                ->select(
                    'ingredientes.idingrediente',
                    'ingredientes.nombre',
                    DB::raw('SUM(detallefacturas.cantidad) as total_comprado')
                )
                ->whereBetween('detallefacturas.emicion', [Carbon::now()->subDays(30)->format('Y-m-d'), Carbon::now()->format('Y-m-d')])
                ->groupBy('ingredientes.idingrediente', 'ingredientes.nombre')
                ->orderBy('total_comprado', 'asc')
                ->first();
        } catch (Exception $e) {
            return response()->json([
                'ingrediente' => null,
                'success'     => false,
                'message'     => 'Error al obtener el ingrediente menos comprado',
                'error'       => $e->getMessage(),
            ]);
        }

        return response()->json([
            'ingrediente' => $Ingrediente,
            'success'     => true,
            'message'     => 'Ingrediente menos comprado',
        ]);
    }
}
