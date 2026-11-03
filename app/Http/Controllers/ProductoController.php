<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Preparacion;
use App\Models\Receta;
use App\Services\CostoProductoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Exception;

class ProductoController extends Controller
{
    /**
     * Display the menu with products from database.
     */
    public function menu()
    {
        $productos = Producto::where('visible', true)
            ->where('stock', '>', 0)
            ->whereIn(DB::raw('LOWER(TRIM(estado))'), ['available', 'disponible', 'activo'])
            ->orderBy('nombre')
            ->get();
        $populares = Producto::where('visible', true)
            ->where('stock', '>', 0)
            ->where(function ($query) {
                $query->whereIn(DB::raw('LOWER(TRIM(estado))'), ['available', 'disponible', 'activo'])
                      ->orWhere('estado', env('ESTADO_PRODUCTO_DISPOBIBLE', 'available'));
            })
            ->withSum('detalleproductos as total_vendidos', 'cantidad')
            ->orderByDesc('total_vendidos')
            ->orderBy('idproducto', 'asc')
        ->take(6)
            ->get();

        $productosParaEncargo = Producto::where('visible', true)
            ->orderBy('nombre')
            ->get();

        return view('menu', compact('productos', 'populares', 'productosParaEncargo'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $recetas = Receta::all();
        $productos = Producto::where('visible', true)->with('recetas')->orderBy('nombre')->get();
        return view('productos', compact('recetas', 'productos'));
    }

    public function listarProductosPorLimite(Request $request)
    {
        $skip = (int) ($request->skip ?? 0);
        $limit = (int) ($request->limit ?? 12);
        $search = trim($request->search ?? '');

        $query = Producto::where('visible', true)
            ->where('stock', '>', 0)
            ->where(function ($q) {
            $q->whereIn(DB::raw('LOWER(TRIM(estado))'), ['available', 'disponible', 'activo'])
              ->orWhere('estado', env('ESTADO_PRODUCTO_DISPOBIBLE', 'available'));
        });

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        $productos = $query->orderBy('nombre', 'asc')->skip($skip)->take($limit)->get();
        return response()->json($productos);
    }

    public function listarPopulares(Request $request)
    {
        $limit = (int) ($request->limit ?? 8);

        $populares = Producto::where('visible', true)
            ->where('stock', '>', 0)
            ->where(function ($query) {
                $query->whereIn(DB::raw('LOWER(TRIM(estado))'), ['available', 'disponible', 'activo'])
                      ->orWhere('estado', env('ESTADO_PRODUCTO_DISPOBIBLE', 'available'));
            })
            ->withSum('detalleproductos as total_vendidos', 'cantidad')
            ->orderByDesc('total_vendidos')
            ->orderBy('idproducto', 'asc')
            ->take($limit)
            ->get();

        return response()->json($populares);
    }

    public function costoReceta(Receta $receta, CostoProductoService $costoProducto)
    {
        return response()->json([
            'costo' => $costoProducto->calcularUnitario($receta->idreceta),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'productName' => ['required', 'string', 'max:60'],
            'productDescription' => ['required', 'string'],
            'productPrice' => ['required', 'numeric', 'min:0'],
            'productQuantity' => ['required', 'integer', 'min:0'],
            'productPicture' => ['nullable', 'image', 'max:4096'],
            'recipe_id' => ['required', 'exists:recetas,idreceta'],
            'status' => ['required', 'in:available,unavailable'],
        ]);

        $costo = app(CostoProductoService::class)->calcularUnitario((int) $validated['recipe_id']);

        $producto = new Producto([
            'nombre' => $validated['productName'],
            'descripcion' => $validated['productDescription'],
            'precio' => $validated['productPrice'],
            'costo' => $costo,
            'stock' => $validated['productQuantity'],
            'foto' => '',
            'receta_id' => $validated['recipe_id'],
            'estado' => $validated['status'],
        ]);

        if ($request->hasFile('productPicture')) {
            File::ensureDirectoryExists(public_path('imagenes_producto'));
            $filename = $request->file('productPicture')->hashName();
            $request->file('productPicture')->move(public_path('imagenes_producto'), $filename);
            $producto->foto = url('/imagenes_producto/' . $filename);
        }
        $producto->save();

        return redirect()->route('productos')->with('success', 'Producto creado correctamente.');
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
    public function show(Producto $producto)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $producto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $producto)
    {
        $producto = Producto::findOrFail($producto);
        $validated = $request->validate([
            'productName' => ['required', 'string', 'max:60'],
            'productDescription' => ['required', 'string'],
            'productPrice' => ['required', 'numeric', 'min:0'],
            'productQuantity' => ['required', 'integer', 'min:0'],
            'productPicture' => ['nullable', 'image', 'max:4096'],
            'status' => ['required', 'in:available,unavailable'],
        ]);

        $costo = app(CostoProductoService::class)->calcularUnitario((int) $producto->receta_id);

        $producto->fill([
            'nombre' => $validated['productName'],
            'descripcion' => $validated['productDescription'],
            'precio' => $validated['productPrice'],
            'costo' => $costo,
            'stock' => $validated['productQuantity'],
            'estado' => $validated['status'],
        ]);

        if ($request->hasFile('productPicture')) {
            File::ensureDirectoryExists(public_path('imagenes_producto'));
            $filename = $request->file('productPicture')->hashName();
            $request->file('productPicture')->move(public_path('imagenes_producto'), $filename);
            $producto->foto = url('/imagenes_producto/' . $filename);
        }

        $producto->save();

        return redirect()->route('productos')->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Producto $producto)
    {
        //
    }

    /**
     * Retorna lista de productos para el selector de ofertas.
     * GET /api/productos/listForOferta
     */
    public function getProductosForOferta()
    {
        try {
            $productos = Producto::where('visible', true)->orderBy('nombre', 'asc')->get();
            return response()->json([
                'success'   => true,
                'productos' => $productos,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success'   => false,
                'message'   => 'Error al cargar productos: ' . $e->getMessage(),
                'productos' => [],
            ], 500);
        }
    }
}
