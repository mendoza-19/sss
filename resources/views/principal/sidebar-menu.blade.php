<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rutaActual = @json(request()->route()?->getName());
        const rolId = Number(@json(auth()->user()->empleado?->idrol ?? 0));
        const rolDescripcion = String(@json(strtolower(trim((string) (auth()->user()->empleado?->rol?->descripcion ?? '')))));

        const opciones = [
            {
                tag: 'Dashboard',
                urlIcon: '{{ asset("assets/global/icon-home.svg") }}',
                href: '{{ route("dashboard") }}',
                rutas: ['dashboard'],
                roles: [1, 3, 4]
            },
            {
                tag: 'Proveedores',
                urlIcon: '{{ asset("assets/global/icon-proveedor.svg") }}',
                href: '{{ route("Proveedores") }}',
                rutas: ['Proveedores'],
                roles: [1]
            },
            {
                tag: 'Recibos',
                urlIcon: '{{ asset("assets/global/icon-factura.svg") }}',
                href: '{{ route("recibos") }}',
                rutas: ['recibos'],
                roles: [1]
            },
            {
                tag: 'Gastos del mes',
                urlIcon: '{{ asset("assets/global/icon-factura.svg") }}',
                href: '{{ route("gastos.index") }}',
                rutas: ['gastos.index', 'gastos.store', 'gastos.destroy'],
                roles: [1]
            },
            
            {
                tag: 'Empleados',
                urlIcon: '{{ asset("assets/global/icon-proveedor.svg") }}',
                href: '{{ route("empleados.index") }}',
                rutas: ['empleados.index', 'empleados.actualizar', 'actualizarPerfilEmpleado'],
                roles: [1]
            },
            {
                tag: 'Recetas',
                urlIcon: '{{ asset("assets/global/icon-file.svg") }}',
                href: '{{ route("recetas.index") }}',
                rutas: ['recetas.index', 'crearreceta', 'recetas.update', 'preparaciones.horneado', 'horneados.index'],
                roles: [1, 3]
            },
            {
                tag: 'Horneado',
                urlIcon: '{{ asset("assets/global/icon-factura.svg") }}',
                href: '{{ route("horneados.index") }}',
                rutas: ['horneados.index', 'preparaciones.horneado'],
                roles: [1, 3]
            },
            {
                tag: 'Horneado',
                urlIcon: '{{ asset("assets/global/icon-factura.svg") }}',
                href: '{{ route("horneados.index") }}',
                rutas: ['horneados.index', 'preparaciones.horneado'],
                roles: [1, 3]
            },
            {
                tag: 'Mermas y devoluciones',
                urlIcon: '{{ asset("assets/global/icon-file.svg") }}',
                href: '{{ route("mermas.index") }}',
                rutas: ['mermas.index'],
                roles: [1]
            },
            {
                tag: 'Productos',
                urlIcon: '{{ asset("assets/global/icon-file.svg") }}',
                href: '{{ route("productos") }}',
                rutas: ['productos', 'crearproducto', 'productos.update'],
                roles: [1, 3]
            },
            {
                tag: 'Ventas',
                urlIcon: '{{ asset("assets/global/icon-factura.svg") }}',
                href: '{{ route("ventas.index") }}',
                rutas: ['ventas.index', 'ventas.store'],
                roles: [1]
            },
            {
                tag: 'Mis asignaciones',
                urlIcon: '{{ asset("assets/global/icon-factura.svg") }}',
                href: '{{ route("ventas.index") }}',
                rutas: ['ventas.index'],
                roles: [2]
            },
            {
                tag: 'Ingredientes',
                urlIcon: '{{ asset("assets/global/icon-file.svg") }}',
                href: '{{ route("ingredientes.index") }}',
                rutas: ['ingredientes.index'],
                roles: [1, 3]
            },
            {
                tag: 'Pedidos asignados',
                urlIcon: '{{ asset("assets/global/icon-pedido.svg") }}',
                href: '{{ route("pedidos") }}',
                rutas: ['pedidos'],
                roles: [4]
            },
            {
                tag: 'Pedidos y ofertas',
                urlIcon: '{{ asset("assets/global/icon-pedido.svg") }}',
                href: '{{ route("pedidos") }}',
                rutas: ['pedidos'],
                roles: [1]
            },
            {
                tag: 'clientes',
                urlIcon: '{{asset("assets/global/icon-clientes.svg")}}',
                href: '{{route("clientes.manager")}}',
                rutas: ['clientes.manager'],
                roles: [1]
            }
        ];

        opciones.filter(function (opcion) {
            const tieneRolPorId = opcion.roles.includes(rolId);
            const tieneRolPorNombre = opcion.roles.some(function (rol) {
                if (rol === 1 && rolDescripcion === 'administrador') return true;
                if (rol === 3 && rolDescripcion === 'panadero') return true;
                if (rol === 4 && rolDescripcion === 'repartidor') return true;
                return false;
            });
            return tieneRolPorId || tieneRolPorNombre;
        }).forEach(function (opcion) {
            addOptionSidebar({
                tag: opcion.tag,
                urlIcon: opcion.urlIcon,
                href: opcion.href,
                isSelected: opcion.rutas.includes(rutaActual)
            });
        });
    });
</script>