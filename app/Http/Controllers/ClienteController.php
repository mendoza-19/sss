<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Cliente::with('user')->get());
    }

    public function clientesManagerView(Request $request)
    {
        return view('clientes');
    }

    public function clienteMasActivo(Request $request)
    {
        try {
            $response = DB::table('clientes as c')
                ->select(
                    'c.nombre as nombre',
                    DB::raw('count(p.idpedido) as pedidos')
                )
                ->join('pedidos as p', 'p.cliente_id', '=', 'c.idcliente')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->join('ventas as v', 'v.idventa', '=', 'd.venta_id')
                ->whereNotNull('v.fechapago')
                ->groupBy('c.nombre')
                ->orderBy('c.nombre', 'desc')->first();
            return response()->json([
                'success' => true,
                'response' => $response,
                'message' => 'Cliente obtenido con exito'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function productoEnTendencia(Request $request)
    {
        try {
            $response = DB::table('productos as p')
                ->join('detalleproductos as d', 'd.producto_id', '=', 'p.idproducto')
                ->select('p.nombre as nombre', DB::raw('SUM(d.cantidad) as cantidad'))
                ->whereBetween('d.created_at', [Carbon::now()->subDays(7)->format('Y-m-d'), Carbon::now()->format('Y-m-d')])
                ->groupBy('p.idproducto', 'p.nombre')
                ->orderBy(DB::raw('SUM(d.cantidad)'), 'desc')
                ->first();
            return response()->json([
                'success' => true,
                'response' => $response,
                'message' => 'Producto en tendencia obtenido con exito.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function clienteMenosActivo(Request $request)
    {
        try {
            $response = DB::table('clientes as c')
                ->select(
                    'c.nombre as nombre',
                    DB::raw('count(p.idpedido) as pedidos')
                )
                ->join('pedidos as p', 'p.cliente_id', '=', 'c.idcliente')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->join('ventas as v', 'v.idventa', '=', 'd.venta_id')
                ->whereNotNull('v.fechapago')
                ->groupBy('c.nombre')
                ->orderBy('c.nombre', 'asc')->first();
            return response()->json([
                'success' => true,
                'response' => $response,
                'message' => 'Cliente obtenido con exito'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function clinetesEstadisticasPorPaginacion(Request $request)
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

            // 1. Subconsulta para la cantidad total de productos comprados
            $subQueryCompra = DB::table('pedidos as p')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->join('ventas as v', 'v.idventa', '=', 'd.venta_id')
                ->select('p.cliente_id', DB::raw('SUM(d.cantidad) as total_cantidad'))
                ->whereNotNull('v.fechapago')
                ->groupBy('p.cliente_id');

            // 2. Subconsulta para la deuda total (ventas no pagadas)
            $subQueryDeuda = DB::table('pedidos as p')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->join('ventas as v', 'v.idventa', '=', 'd.venta_id')
                ->select('p.cliente_id', DB::raw('SUM(d.subtotal) as total_deuda'))
                ->whereNull('v.fechapago')
                ->groupBy('p.cliente_id');

            // 3. Consulta principal uniendo todo
            $clientesReporte = DB::table('clientes as c')
                ->leftJoinSub($subQueryCompra, 'compra', 'compra.cliente_id', '=', 'c.idcliente')
                ->leftJoinSub($subQueryDeuda, 'deuda', 'deuda.cliente_id', '=', 'c.idcliente')
                ->select(
                    'c.idcliente',
                    'c.nombre',
                    'c.apellido',
                    'c.telefono',
                    DB::raw('COALESCE(compra.total_cantidad, 0) as cantidad'),
                    DB::raw('COALESCE(deuda.total_deuda, 0) as deuda')
                )
                ->orderBy('c.assessment','desc')
                ->skip($skip)
                ->take($limit)
                ->get();
            return Response()->json([
                'success' => true,
                'response' => $clientesReporte,
                'message' => 'Clientes adquiridos con exito'
            ]);
        } catch (Exception $e) {
            return Response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function clientesPorComprasPaginacion(Request $request)
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

            // 1. Subconsulta para la cantidad total de productos comprados
            $subQueryCompra = DB::table('pedidos as p')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->join('ventas as v', 'v.idventa', '=', 'd.venta_id')
                ->select('p.cliente_id', DB::raw('SUM(d.cantidad) as total_cantidad'))
                ->whereNotNull('v.fechapago')
                ->groupBy('p.cliente_id');

            // 2. Subconsulta para la deuda total
            $subQueryDeuda = DB::table('pedidos as p')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->join('ventas as v', 'v.idventa', '=', 'd.venta_id')
                ->select('p.cliente_id', DB::raw('SUM(d.subtotal) as total_deuda'))
                ->whereNull('v.fechapago')
                ->groupBy('p.cliente_id');

            // 3. Consulta principal con el filtro de deuda
            $clientesReporte = DB::table('clientes as c')
                ->leftJoinSub($subQueryCompra, 'compra', 'compra.cliente_id', '=', 'c.idcliente')
                ->leftJoinSub($subQueryDeuda, 'deuda', 'deuda.cliente_id', '=', 'c.idcliente')
                ->select(
                    'c.idcliente',
                    'c.nombre',
                    'c.apellido',
                    'c.telefono',
                    DB::raw('COALESCE(compra.total_cantidad, 0) as cantidad'),
                    DB::raw('COALESCE(deuda.total_deuda, 0) as deuda')
                )
                ->where('compra.total_cantidad','>',0)
                ->orderBy('compra.total_cantidad', 'desc')
                ->skip($skip)
                ->take($limit)
                ->get();

            return Response()->json([
                'success' => true,
                'response' => $clientesReporte,
                'message' => 'Clientes adquiridos con exito'
            ]);
        } catch (Exception $e) {
            return Response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function clientePorDeudaPaginacionLegacy(Request $request){
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

            // 1. Subconsulta para la cantidad total de productos comprados
            $subQueryCompra = DB::table('pedidos as p')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->join('ventas as v', 'v.idventa', '=', 'd.venta_id')
                ->select('p.cliente_id', DB::raw('SUM(d.cantidad) as total_cantidad'))
                ->whereNotNull('v.fechapago')
                ->groupBy('p.cliente_id');

            // 2. Subconsulta para la deuda total
            $subQueryDeuda = DB::table('pedidos as p')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->join('ventas as v', 'v.idventa', '=', 'd.venta_id')
                ->select('p.cliente_id', DB::raw('SUM(d.subtotal) as total_deuda'))
                ->whereNull('v.fechapago')
                ->groupBy('p.cliente_id');

            // 3. Consulta principal con el filtro de deuda
            $clientesReporte = DB::table('clientes as c')
                ->leftJoinSub($subQueryCompra, 'compra', 'compra.cliente_id', '=', 'c.idcliente')
                ->leftJoinSub($subQueryDeuda, 'deuda', 'deuda.cliente_id', '=', 'c.idcliente')
                ->select(
                    'c.idcliente',
                    'c.nombre',
                    'c.apellido',
                    'c.telefono',
                    DB::raw('COALESCE(compra.total_cantidad, 0) as cantidad'),
                    DB::raw('COALESCE(deuda.total_deuda, 0) as deuda')
                )
                ->where('compra.total_cantidad','>',0)
                ->orderBy('deuda.total_deuda', 'desc')
                ->skip($skip)
                ->take($limit)
                ->get();

            return Response()->json([
                'success' => true,
                'response' => $clientesReporte,
                'message' => 'Clientes adquiridos con exito'
            ]);
        } catch (Exception $e) {
            return Response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function obtenerClienteSearchPanginacion(Request $request){
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
            if(isset($request->search)){
                $search = $request->search;
            }else{
                $search = "";
            }

            // 1. Subconsulta para la cantidad total de productos comprados
            $subQueryCompra = DB::table('pedidos as p')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->join('ventas as v', 'v.idventa', '=', 'd.venta_id')
                ->select('p.cliente_id', DB::raw('SUM(d.cantidad) as total_cantidad'))
                ->whereNotNull('v.fechapago')
                ->groupBy('p.cliente_id');

            // 2. Subconsulta para la deuda total
            $subQueryDeuda = DB::table('pedidos as p')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->join('ventas as v', 'v.idventa', '=', 'd.venta_id')
                ->select('p.cliente_id', DB::raw('SUM(d.subtotal) as total_deuda'))
                ->whereNull('v.fechapago')
                ->groupBy('p.cliente_id');

            // 3. Consulta principal con el filtro de deuda
            $clientesReporte = DB::table('clientes as c')
                ->leftJoinSub($subQueryCompra, 'compra', 'compra.cliente_id', '=', 'c.idcliente')
                ->leftJoinSub($subQueryDeuda, 'deuda', 'deuda.cliente_id', '=', 'c.idcliente')
                ->join('users as u','u.id','=','c.user_id')
                ->select(
                    'c.idcliente',
                    'c.nombre',
                    'c.apellido',
                    'c.telefono',
                    DB::raw('COALESCE(compra.total_cantidad, 0) as cantidad'),
                    DB::raw('COALESCE(deuda.total_deuda, 0) as deuda')
                )
                ->where('c.nombre','like','%'.$search.'%')
                ->orWhere('c.apellido','like','%'.$search.'%')
                ->orWhere('c.telefono','like','%'.$search.'%')
                ->orWhere('u.email','like','%'.$search.'%')
                ->skip($skip)
                ->take($limit)
                ->get();

            return Response()->json([
                'success' => true,
                'response' => $clientesReporte,
                'message' => 'Clientes adquiridos con exito'
            ]);
        } catch (Exception $e) {
            return Response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function addressAndPerfilPicture(Request $request){
        try{
            if(isset($request->idcliente)){
                $idcliente = $request->idcliente;
            }else{
                $idcliente = 1;
            }
            $response = DB::table('users as u')
                ->select('u.email as email','c.foto as foto')
                ->join('clientes as c','c.user_id','=','u.id')
                ->where('c.idcliente','=',$idcliente)
                ->first();
            return response()->json([
                'success'=>true,
                'message'=>'Address obtenido con exito',
                'response'=>$response
            ]);
        }catch(Exception $e){
            return response()->json([
                'success'=>false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function clientePorDeudaPaginacion(Request $request)
    {
        try {
            $skip = max(0, (int) $request->input('skip', 0));
            $limit = max(1, (int) $request->input('limit', 6));
            $search = trim((string) $request->input('search', ''));

            $subQueryPedidos = DB::table('pedidos as p')
                ->select('p.cliente_id', DB::raw('COUNT(p.idpedido) as total_pedidos'))
                ->where('p.estado_pago', 'pendiente')
                ->groupBy('p.cliente_id');

            $subQueryDeuda = DB::table('pedidos as p')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->select('p.cliente_id', DB::raw('SUM(d.subtotal) as total_deuda'))
                ->where('p.estado_pago', 'pendiente')
                ->groupBy('p.cliente_id');

            $query = DB::table('clientes as c')
                ->leftJoinSub($subQueryPedidos, 'pedidos', 'pedidos.cliente_id', '=', 'c.idcliente')
                ->joinSub($subQueryDeuda, 'deuda', 'deuda.cliente_id', '=', 'c.idcliente')
                ->select(
                    'c.idcliente',
                    'c.nombre',
                    'c.apellido',
                    'c.telefono',
                    DB::raw('COALESCE(pedidos.total_pedidos, 0) as cantidad'),
                    'deuda.total_deuda as deuda'
                )
                ->where('deuda.total_deuda', '>', 0)
                ->orderByDesc('deuda.total_deuda');

            if ($search !== '') {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('c.nombre', 'like', '%' . $search . '%')
                        ->orWhere('c.apellido', 'like', '%' . $search . '%')
                        ->orWhere('c.telefono', 'like', '%' . $search . '%');
                });
            }

            $clientesReporte = $query->skip($skip)->take($limit)->get();

            return response()->json([
                'success' => true,
                'response' => $clientesReporte,
                'message' => 'Clientes con deuda obtenidos correctamente.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function obtenerClienteSearchPanginacionLegacy(Request $request)
    {
        try {
            $skip = max(0, (int) $request->input('skip', 0));
            $limit = max(1, (int) $request->input('limit', 6));
            $search = trim((string) $request->input('search', ''));

            $subQueryCompra = DB::table('pedidos as p')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->join('ventas as v', 'v.idventa', '=', 'd.venta_id')
                ->select('p.cliente_id', DB::raw('SUM(d.cantidad) as total_cantidad'))
                ->whereNotNull('v.fechapago')
                ->groupBy('p.cliente_id');

            $subQueryDeuda = DB::table('pedidos as p')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->join('ventas as v', 'v.idventa', '=', 'd.venta_id')
                ->select('p.cliente_id', DB::raw('SUM(d.subtotal) as total_deuda'))
                ->whereNull('v.fechapago')
                ->groupBy('p.cliente_id');

            $query = DB::table('clientes as c')
                ->leftJoinSub($subQueryCompra, 'compra', 'compra.cliente_id', '=', 'c.idcliente')
                ->leftJoinSub($subQueryDeuda, 'deuda', 'deuda.cliente_id', '=', 'c.idcliente')
                ->join('users as u', 'u.id', '=', 'c.user_id')
                ->select(
                    'c.idcliente',
                    'c.nombre',
                    'c.apellido',
                    'c.telefono',
                    DB::raw('COALESCE(compra.total_cantidad, 0) as cantidad'),
                    DB::raw('COALESCE(deuda.total_deuda, 0) as deuda')
                );

            if ($search !== '') {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('c.nombre', 'like', '%' . $search . '%')
                        ->orWhere('c.apellido', 'like', '%' . $search . '%')
                        ->orWhere('c.telefono', 'like', '%' . $search . '%')
                        ->orWhere('u.email', 'like', '%' . $search . '%');
                });
            }

            $clientesReporte = $query->skip($skip)->take($limit)->get();

            return response()->json([
                'success' => true,
                'response' => $clientesReporte,
                'message' => 'Clientes adquiridos con exito'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function addressAndPerfilPictureLegacy(Request $request)
    {
        try {
            $idcliente = $request->input('idcliente', 1);
            $response = DB::table('users as u')
                ->select('u.email as email', 'c.foto as foto')
                ->join('clientes as c', 'c.user_id', '=', 'u.id')
                ->where('c.idcliente', '=', $idcliente)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Address obtenido con exito',
                'response' => $response
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function obtenerAssessment(Request $request)
    {
        try {
            $idcliente = $request->input('idcliente', 1);
            $response = DB::table('clientes as c')
                ->select('c.nombre as nombre', 'c.comment as comment', 'c.assessment as assessment')
                ->where('c.idcliente', '=', $idcliente)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Evaluaciones obtenidas con exito',
                'response' => $response
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function guardarAssessment(Request $request)
    {
        try {
            $cliente = Cliente::findOrFail($request->input('idcliente', 1));
            if ($request->has('comment')) $cliente->comment = $request->comment;
            $cliente->assessment = $request->input('assessment', 0);
            $cliente->save();

            return response()->json([
                'success' => true,
                'message' => 'Evaluaciones guardadas con exito'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getClientesForOferta(Request $request)
    {
        try {
            $search = trim($request->search ?? '');
            $query = Cliente::with(['user'])->withCount('ofertas');

            if ($search !== '') {
                $query->where(function ($clienteQuery) use ($search) {
                    $clienteQuery->where('nombre', 'like', '%' . $search . '%')
                        ->orWhere('apellido', 'like', '%' . $search . '%')
                        ->orWhere('telefono', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('email', 'like', '%' . $search . '%')
                                ->orWhere('name', 'like', '%' . $search . '%');
                        });
                });
            }

            return response()->json([
                'success' => true,
                'clientes' => $query->orderBy('nombre', 'asc')->get(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes: ' . $e->getMessage(),
                'clientes' => [],
            ], 500);
        }
    }

    public function searchClientesForOferta(Request $request)
    {
        return $this->getClientesForOferta($request);
    }
}
