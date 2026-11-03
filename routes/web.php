<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\profileUserController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\RecetaController;
use App\Http\Controllers\dashboard;
use App\Http\Controllers\EmailManagerController;
use App\Http\Controllers\SocialiteManagerController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\IngredienteController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\PreparacionController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\MermaController;

use App\Http\Controllers\ProporcionesrecetaController;
use App\Http\Controllers\EmployeePasswordSetupController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\OfertaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\LogAdminController;

use App\Http\Controllers\BackupController;

//test inport Storage
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\GastoMensualController;

//ruta por defecto o raiz
Route::get('/', [ProductoController::class, 'menu'])->name('menu');

//rutas de usuario o autenticacion
Route::middleware('guest')->group(function () {
    Route::view('/login', 'login')->name('login');
    Route::view('/registro', 'registro')->name('registro');
    Route::redirect('/register', '/registro')->name('register');
});
Route::post('/validar-registro', [App\Http\Controllers\LoginController::class, 'registro'])->name('validar-registro');
Route::get('/logout', [App\Http\Controllers\LoginController::class, 'logout'])->name('logout');
Route::post('/validar-login', [App\Http\Controllers\LoginController::class, 'login'])->name('validar-login');


//rutas de verificacion de correo electronico
Route::view('/email/verify', 'auth.verificar-email')->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [EmailManagerController::class, 'emailVerify'])->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/verification-notification', [EmailManagerController::class, 'resendEmailVeirify'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');


//rutas de dashboard
Route::get('/dashboard', [dashboard::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
Route::middleware(['auth', 'verified', 'role:Administrador,Panadero,Repartidor'])->group(function () {
    Route::get('/dashboard/total-productos-vendidos', [dashboard::class, 'totalProductosVendidos'])->name('dashboard.totalProductosVendidos');
    Route::get('/dashboard/ganancias-del-dia', [dashboard::class, 'gananciasDelDia'])->name('dashboard.gananciasDelDia');
    Route::get('/dashboard/productos-mas-vendidos', [dashboard::class, 'productosMasVendidos'])->name('dashboard.productosMasVendidos');
    Route::get('/dashboard/total-por-cobrar', [dashboard::class, 'totalPorCobrar'])->name('totalPorCobrar');
    Route::get('/dashboard/ventas-por-mes', [dashboard::class, 'ventasPorMesChart'])->name('ventasPorMes');
    Route::get('/dashboard/produccion-por-mes', [dashboard::class, 'produccionPorMes'])->name('dashboard.produccionPorMes');
    Route::get('/dashboard/desglose-mensual', [dashboard::class, 'desgloseMensual'])->name('dashboard.desgloseMensual');
    Route::get('/dashboard/analitica', [dashboard::class, 'analitica'])->name('dashboard.analitica');
});

//rutas de google socialite
Route::get('/auth/google', [SocialiteManagerController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [SocialiteManagerController::class, 'handleGoogleCallback'])->name('google.callback');

//rutas de perfil
Route::get('/perfil', [EmpleadoController::class, 'perfil'])->name("perfil")->middleware("auth");
Route::post('/actualizarPerfil', [profileUserController::class, 'update'])->middleware('auth')->name("actualizarPerfil");
Route::post('/actualizarPerfilEmpleado', [EmpleadoController::class, 'actualizarPerfil'])->middleware('auth')->name("actualizarPerfilEmpleado");
Route::post('/actualizarPassword', [profileUserController::class, 'updatePassword'])->middleware('auth')->name("actualizarPassword");

//rutas de productos
Route::middleware(['auth', 'verified', 'role:Administrador,Panadero'])->group(function () {
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos');
    Route::post('/crearproducto', [ProductoController::class, 'create'])->name('crearproducto');
});
Route::middleware(['auth', 'verified', 'role:Administrador'])->group(function () {
    Route::put('/productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
});

//rutas de ventas y sus detalles
Route::middleware(['auth', 'verified', 'role:Administrador,Vendedor'])->group(function () {
    Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
});
Route::middleware(['auth', 'verified', 'role:Administrador'])->group(function () {
    Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    Route::post('/ventas/procesar-pedido', [VentaController::class, 'procesarPedido'])->name('ventas.procesar-pedido');
    Route::post('/ventas/encargos/local', [PedidoController::class, 'crearEncargoLocal'])->name('ventas.encargos.local.store');
    Route::post('/api/ventas/clientes', [VentaController::class, 'crearClienteLocal'])->name('api.ventas.clientes.store');
    Route::get('/api/ventas/vendedores/historial', [VentaController::class, 'historialVendedores'])
        ->name('api.ventas.vendedores.historial');
});
Route::post('/ventas/asignaciones/{ventaambulante}/cerrar', [VentaController::class, 'cerrarAsignacion'])
    ->middleware(['auth', 'verified', 'role:Vendedor'])
    ->name('ventas.asignaciones.cerrar');
Route::get('/api/ventas/asignaciones', [VentaController::class, 'listarAsignaciones'])
    ->middleware(['auth', 'verified', 'role:Administrador,Vendedor'])
    ->name('api.ventas.asignaciones');

//rutas de recetas
Route::middleware(['auth', 'verified', 'role:Administrador,Panadero'])->group(function () {
    Route::get('/recetas', [RecetaController::class, 'index'])->name('recetas.index');
    Route::get('/horneados', [PreparacionController::class, 'indexHorneado'])->name('horneados.index');
    Route::get('/preparaciones/{preparacion}/horneado', [PreparacionController::class, 'horneado'])->name('preparaciones.horneado');
    Route::post('/preparaciones/{preparacion}/hornear', [PreparacionController::class, 'storeHorneado'])->name('preparaciones.hornear');
});
Route::middleware(['auth', 'verified', 'role:Administrador'])->group(function () {
    Route::post('/crearreceta', [RecetaController::class, 'create'])->name('crearreceta');
    Route::put('/recetas/{receta}', [RecetaController::class, 'update'])->name('recetas.update');
});
Route::get('/mermas', [MermaController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:Administrador'])
    ->name('mermas.index');
Route::middleware(['auth', 'verified', 'role:Administrador'])->group(function () {
    Route::get('/gastos', [GastoMensualController::class, 'index'])->name('gastos.index');
    Route::post('/gastos', [GastoMensualController::class, 'store'])->name('gastos.store');
    Route::delete('/gastos/{gastoMensual}', [GastoMensualController::class, 'destroy'])->name('gastos.destroy');
});
// Rutas de restablecimiento de contraseña
Route::get('/restablecer-password', [PasswordResetLinkController::class, 'pagePasswordReset'])->middleware('guest')->name('password-restore');
Route::post('/verificar-correo-restablecer', [PasswordResetLinkController::class, 'checkEmail'])->middleware('guest')->name('password.check-email');
Route::post('/enviar-enlace-restablecer', [PasswordResetLinkController::class, 'store'])->middleware('guest')->name('password.email');
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->middleware('guest')->name('password.reset');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('guest')->name('password.store');
Route::get('/empleado/establecer-password/{token}', [EmployeePasswordSetupController::class, 'create'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('empleados.password.reset');
Route::post('/empleado/establecer-password', [EmployeePasswordSetupController::class, 'store'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('empleados.password.store');


//api para productos
Route::get('/api/productos', [ProductoController::class, 'listarProductosPorLimite'])->name('api.productos');
Route::get('/api/productos/populares', [ProductoController::class, 'listarPopulares'])->name('api.productos.populares');
Route::get('/api/recetas/{receta}/costo-producto', [ProductoController::class, 'costoReceta'])
    ->middleware(['auth', 'verified', 'role:Administrador,Panadero'])
    ->name('api.recetas.costoProducto');

//rutas de proveedores
Route::middleware(['auth', 'verified', 'role:Administrador'])->group(function () {
    Route::get('/Proveedores', [ProveedorController::class, 'index'])->name('Proveedores');
    Route::post('/api/proveedores/getProveedoresPagination', [ProveedorController::class, 'getProveedoresPagination'])->name('api.proveedores.getProveedoresPagination');
    Route::post('/api/proveedores/create', [ProveedorController::class, 'create'])->name('api.proveedores.create');
    Route::post('/api/proveedores/updateProveedor', [ProveedorController::class, 'updateProveedor'])->name('api.proveedores.update');
    Route::get('/api/proveedores/proveedroMenosSolicitado', [ProveedorController::class, 'proveedroMenosSolicitado'])->name('api.proveedores.proveedroMenosSolicitado');
    Route::post('/api/proveedores/searchProveedor', [ProveedorController::class, 'searchProveedor'])->name('api.proveedores.searchProveedor');
    Route::post('/api/proveedores/graphiGastosEnCompras', [ProveedorController::class, 'graphiGastosEnCompras'])->name('api.proveedores.graphiGastosEnCompras');
    Route::get('/proveedores/reporte', [ProveedorController::class, 'generatePDF'])->name('proveedores.reporte');
    Route::post('/api/proveedores/updateScore', [ProveedorController::class, 'updateScore'])->name('api.proveedores.updateScore');
});
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
    Route::get('/empleados/create', [EmpleadoController::class, 'create'])->name('empleados.create');
    Route::post('/empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
    Route::post('/empleados/seleccionar', [EmpleadoController::class, 'seleccionar'])->name('empleados.seleccionar');
    Route::put('/empleados', [EmpleadoController::class, 'actualizar'])->name('empleados.actualizar');
    Route::get('/municipios/{iddepartamento}', [EmpleadoController::class, 'municipios'])->name('empleados.municipios');
    Route::get('/distritos/{idmunicipio}', [EmpleadoController::class, 'distritos'])->name('empleados.distritos');
    Route::post('/api/empleados/getEmpleadosPagination', [EmpleadoController::class, 'getEmpleadosPagination'])->name('api.empleados.getEmpleadosPagination');
    Route::post('/api/empleados/searchEmpleado', [EmpleadoController::class, 'searchEmpleado'])->name('api.empleados.searchEmpleado');
    Route::get('/api/empleados/opcionesFormulario', [EmpleadoController::class, 'opcionesFormulario'])->name('api.empleados.opcionesFormulario');
    Route::get('/api/empleados/{idempleado}', [EmpleadoController::class, 'detalleEmpleado'])->name('api.empleados.show');
    Route::post('/api/empleados/create', [EmpleadoController::class, 'crearEmpleado'])->name('api.empleados.create');
    Route::post('/api/empleados/updateEmpleado', [EmpleadoController::class, 'actualizarEmpleado'])->name('api.empleados.update');
    Route::post('/api/empleados/{idempleado}/reenviar-enlace-password', [EmpleadoController::class, 'reenviarEnlacePassword'])
        ->middleware('throttle:6,1')
        ->name('api.empleados.password.resend');
});
Route::post('/api/proveedores/getProveedoresPagination', [ProveedorController::class, 'getProveedoresPagination'])->name('api.proveedores.getProveedoresPagination');
Route::post('/api/proveedores/create', [ProveedorController::class, 'create'])->name('api.proveedores.create');
Route::post('/api/proveedores/updateProveedor', [ProveedorController::class, 'updateProveedor'])->name('api.proveedores.update');
Route::get('/api/proveedores/proveedroMenosSolicitado', [ProveedorController::class, 'proveedroMenosSolicitado'])->name('api.proveedores.proveedroMenosSolicitado');
Route::post('/api/proveedores/searchProveedor', [ProveedorController::class, 'searchProveedor'])->name('api.proveedores.searchProveedor');
Route::post('/api/proveedores/graphiGastosEnCompras', [ProveedorController::class, 'graphiGastosEnCompras'])->name('api.proveedores.graphiGastosEnCompras');
Route::get('/proveedores/reporte', [ProveedorController::class, 'generatePDF'])->name('proveedores.reporte');
Route::post('/api/proveedores/updateScore', [ProveedorController::class, 'updateScore'])->name('api.proveedores.updateScore');

//rutas de ingredientes
Route::middleware(['auth', 'verified', 'role:Administrador,Panadero'])->group(function () {
    Route::get('/ingredientes', [IngredienteController::class, 'index'])->name('ingredientes.index');
    Route::get('/api/ingredientes/ingredienteMasComprado', [IngredienteController::class, 'ingredienteMasComprado'])->name('api.ingredientes.ingredienteMasComprado');
    Route::get('/api/ingredientes/ingredienteMenosComprado', [IngredienteController::class, 'ingredienteMenosComprado'])->name('api.ingredientes.ingredienteMenosComprado');
    Route::get('/api/ingredientes/allIngredients', [IngredienteController::class, 'allIngredients'])->name('api.ingredientes.allIngredients');
    Route::get('/api/ingredientes/estadisticas', [IngredienteController::class, 'estadisticas'])->name('api.ingredientes.estadisticas');
    Route::get('/api/ingredientes/proximosVencer', [IngredienteController::class, 'proximosVencer'])->name('api.ingredientes.proximosVencer');
    Route::post('/api/ingredientes/getIngredientesPagination', [IngredienteController::class, 'getIngredientesPagination'])->name('api.ingredientes.getIngredientesPagination');
    Route::post('/api/ingredientes/searchIngrediente', [IngredienteController::class, 'searchIngrediente'])->name('api.ingredientes.searchIngrediente');
});
Route::middleware(['auth', 'verified', 'role:Administrador'])->group(function () {
    Route::post('/api/ingredientes/crearIngrediente', [IngredienteController::class, 'crearIngrediente'])->name('api.ingredientes.crearIngrediente');
    Route::post('/api/ingredientes/actualizarIngrediente', [IngredienteController::class, 'actualizarIngrediente'])->name('api.ingredientes.actualizarIngrediente');
});


//rutas de facturas y gestion de recibos
Route::middleware(['auth', 'verified', 'role:Administrador'])->group(function () {
    Route::get('/recibos', [FacturaController::class, 'index'])->name('recibos');
    Route::get('/recibos/reporte', [FacturaController::class, 'generatePDF'])->name('recibos.reporte');
    Route::post('/api/facturas/getFacturasPagination', [FacturaController::class, 'getFacturasPagination'])->name('api.facturas.getFacturasPagination');
    Route::post('/api/facturas/getFacturasByProveedorPagination', [FacturaController::class, 'getFacturasByProveedorPagination'])->name('api.facturas.getFacturasByProveedorPagination');
    Route::get('/api/facturas/estadisticas', [FacturaController::class, 'getEstadisticas'])->name('api.facturas.estadisticas');
    Route::get('/api/facturas/gastosMensuales', [FacturaController::class, 'getGastosMensuales'])->name('api.facturas.gastosMensuales');
    Route::get('/api/facturas/detalle/{id}', [FacturaController::class, 'getDetalleFactura'])->name('api.facturas.detalle');
    Route::post('/api/facturas/create', [FacturaController::class, 'store'])->name('api.facturas.create');
    Route::post('/api/facturas/update', [FacturaController::class, 'update'])->name('api.facturas.update');
});

Route::middleware(['auth', 'verified', 'role:Administrador,Repartidor'])->group(function () {
    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos');
    Route::get('/api/pedidos/estadisticas', [PedidoController::class, 'estadisticas'])->name('api.pedidos.estadisticas');
    Route::post('/api/pedidos/getPedidosPagination', [PedidoController::class, 'getPedidosPagination'])->name('api.pedidos.getPedidosPagination');
    Route::post('/api/pedidos/cambiarEstado', [PedidoController::class, 'cambiarEstado'])->name('api.pedidos.cambiarEstado');
    Route::post('/api/pedidos/cambiarEstadoProduccion', [PedidoController::class, 'cambiarEstadoProduccion'])->name('api.pedidos.cambiarEstadoProduccion');
    Route::post('/api/pedidos/cambiarEstadoPago', [PedidoController::class, 'cambiarEstadoPago'])->name('api.pedidos.cambiarEstadoPago');
    Route::post('/api/pedidos/asignarRepartidor', [PedidoController::class, 'asignarRepartidor'])->name('api.pedidos.asignarRepartidor');
    Route::post('/api/pedidos/crear', [PedidoController::class, 'store'])->name('api.pedidos.crear');
});
Route::post('/api/pedidos/cliente/crear', [PedidoController::class, 'crearPedidoCliente'])
    ->middleware('auth')
    ->name('api.pedidos.cliente.crear');
Route::post('/api/pedidos/cliente/encargo', [PedidoController::class, 'crearEncargo'])
    ->middleware('auth')
    ->name('api.pedidos.cliente.encargo');
Route::middleware(['auth', 'verified', 'role:Administrador'])->group(function () {
    Route::get('/api/ofertas/list', [OfertaController::class, 'getOfertas'])->name('api.ofertas.list');
    Route::post('/api/ofertas/create', [OfertaController::class, 'store'])->name('api.ofertas.create');
    Route::delete('/api/ofertas/{id}', [OfertaController::class, 'destroy'])->name('api.ofertas.destroy');
    Route::post('/api/ofertas/delete', [OfertaController::class, 'destroyPost'])->name('api.ofertas.delete');
    Route::get('/api/clientes/listForOferta', [ClienteController::class, 'getClientesForOferta'])->name('api.clientes.listForOferta');
    Route::post('/api/clientes/searchForOferta', [ClienteController::class, 'searchClientesForOferta'])->name('api.clientes.searchForOferta');
    Route::get('/api/productos/listForOferta', [ProductoController::class, 'getProductosForOferta'])->name('api.productos.listForOferta');
});

Route::view('/test', 'test')->middleware(['auth', 'verified', 'role:Administrador'])->name('test');

Route::middleware(['auth', 'verified', 'role:Administrador,Panadero'])->group(function () {
    Route::post('/api/proporcionesreceta/guardar', [ProporcionesrecetaController::class, 'store'])
        ->name('api.proporcionesreceta.guardar');
    Route::get('/api/proporcionesreceta/buscar-ingredientes', [ProporcionesrecetaController::class, 'buscarIngredientes'])
        ->name('api.proporcionesreceta.buscarIngredientes');
    Route::post('/preparaciones/procesar', [PreparacionController::class, 'procesar'])
        ->name('preparaciones.procesar');
});

//rutas de gestion de clientes (vista que manejan los empleados)
Route::get('/clientesManager',[ClienteController::class, 'clientesManagerView'])
    ->middleware(['verified', 'auth'])
    ->name('clientes.manager');
Route::get('/clienteManager/mas-activo',[ClienteController::class,'clienteMasActivo'])
    ->middleware(['verified','auth'])
    ->name('clienteMasActivo');
Route::get('/clienteManager/tendencia',[ClienteController::class,'productoEnTendencia'])
    ->middleware(['verified','auth'])
    ->name('productoEnTendencia');
Route::get('/clienteManager/menos-activo',[ClienteController::class,'clienteMenosActivo'])
    ->middleware(['verified','auth'])
    ->name('clienteMenosActivo');
Route::post('/clienteManager/lita-clientes',[ClienteController::class,'clinetesEstadisticasPorPaginacion'])
    ->middleware(['verified','auth'])
    ->name('lista-clientes');
Route::post('/clienteManager/lita-cliente-compras',[ClienteController::class,'clientesPorComprasPaginacion'])
    ->middleware(["verified","auth"])
    ->name('clientes-por-compras');
Route::post('/clienteManager/lista-cliente-compras',[ClienteController::class, 'clientePorDeudaPaginacion'])
    ->middleware(['verified','auth'])
    ->name('clientes-por-deuda');
Route::post('/clienteManager/search-cliente',[ClienteController::class,'obtenerClienteSearchPanginacion'])
    ->middleware(['verified','auth'])
    ->name('search-cliente');
Route::post('/clienteManager/address-and-profile',[ClienteController::class,'addressAndPerfilPicture'])
    ->middleware('verified','auth')
    ->name('address-and-profile');
Route::post('/clienteManager/assessment',[ClienteController::class,'obtenerAssessment'])
    ->middleware(['verified','auth'])
    ->name('assessment-client');
Route::post('/clienteManager/update-assessment',[ClienteController::class,'guardarAssessment'])
    ->middleware(['verified','auth'])
    ->name('update-assessment');


//rutas de logger del sistema
Route::get('/logsView',[LogAdminController::class ,'index'])
    ->middleware('verified','auth')
    ->name('admin.logs');

//rutas de backup y restauracion de base de datos
Route::middleware(['auth'])->prefix('admin/backups')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/create', [BackupController::class, 'create'])->name('backups.create');
    Route::post('/restore', [BackupController::class, 'restore'])->name('backups.restore');
});


//test driver google
Route::get('/test-google-drive', function () {
    try {
        $disk = Storage::disk('google');
        $files = $disk->allFiles();
        return response()->json(['files' => $files]);
    } catch (Exception $e) {
        return response()->json(['error' => 'Error al acceder a Google Drive: ' . $e->getMessage()], 500);
    }
});


Route::get('/test-google-drive-file', function () {
    try {
        Storage::disk('google')->put('test.txt', 'Este es un archivo de prueba en Google Drive.');
        return response()->json(['success' => 'Archivo de prueba creado en Google Drive.']);
    } catch (Exception $e) {
        return response()->json(['error' => 'Error al acceder a Google Drive: ' . $e->getMessage()], 500);
    }
});