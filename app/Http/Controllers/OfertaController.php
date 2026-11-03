<?php

namespace App\Http\Controllers;

use App\Models\Oferta;
use App\Models\Cliente;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class OfertaController extends Controller
{
    /**
     * Retorna todas las ofertas registradas con sus relaciones.
     * GET /api/ofertas/list
     */
    public function getOfertas()
    {
        try {
            $ofertas = Oferta::with(['cliente.user', 'productoRel'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'ofertas' => $ofertas,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ofertas: ' . $e->getMessage(),
                'ofertas' => [],
            ], 500);
        }
    }

    /**
     * Guarda una nueva oferta para un cliente y producto.
     * POST /api/ofertas/create
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fechainicio' => 'required|date',
                'fechafin'    => 'required|date|after_or_equal:fechainicio',
                'descuento'   => 'required|numeric|min:1',
                'cliente_id'  => 'required|exists:clientes,idcliente',
                'producto'    => 'required|exists:productos,idproducto',
            ], [
                'fechainicio.required' => 'La fecha de inicio es obligatoria.',
                'fechafin.required'    => 'La fecha de fin es obligatoria.',
                'fechafin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
                'descuento.required'   => 'El monto de descuento es obligatorio.',
                'descuento.min'        => 'El descuento debe ser mayor a 0.',
                'cliente_id.required'  => 'Debe seleccionar un cliente de la lista.',
                'cliente_id.exists'    => 'El cliente seleccionado no existe.',
                'producto.required'    => 'Debe seleccionar un producto.',
                'producto.exists'      => 'El producto seleccionado no existe.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $oferta = Oferta::create([
                'fechainicio' => $request->fechainicio,
                'fechafin'    => $request->fechafin,
                'descuento'   => (int)$request->descuento,
                'cliente_id'  => $request->cliente_id,
                'producto'    => $request->producto,
            ]);

            $oferta->load(['cliente.user', 'productoRel']);

            return response()->json([
                'success' => true,
                'message' => 'Oferta creada exitosamente.',
                'oferta'  => $oferta,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la oferta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina una oferta por ID (DELETE o POST).
     */
    public function destroy($id)
    {
        try {
            $oferta = Oferta::find($id);
            if (!$oferta) {
                return response()->json([
                    'success' => false,
                    'message' => 'La oferta no fue encontrada.',
                ], 404);
            }

            $oferta->delete();

            return response()->json([
                'success' => true,
                'message' => 'Oferta eliminada correctamente.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar oferta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina una oferta via POST con id en el payload.
     * POST /api/ofertas/delete
     */
    public function destroyPost(Request $request)
    {
        $id = $request->idoferta ?? $request->id;
        return $this->destroy($id);
    }
}
