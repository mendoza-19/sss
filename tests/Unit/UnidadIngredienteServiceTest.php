<?php

use App\Services\UnidadIngredienteService;

test('convierte unidades de peso al destino correcto', function () {
    $service = new UnidadIngredienteService();

    expect($service->convertirAStock(1, 'oz', 'lb'))->toEqual(0.063)
        ->and($service->convertirAStock(16, 'oz', 'lb'))->toEqual(1)
        ->and($service->convertirAStock(1, 'lb', 'oz'))->toEqual(16)
        ->and($service->convertirAStock(28.3495, 'g', 'oz'))->toEqual(1);
});
