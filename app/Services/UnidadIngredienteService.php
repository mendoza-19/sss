<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UnidadIngredienteService
{
    public function convertirAStock(float $cantidad, string $unidad, string $unidadStock): float
    {
        $origen = $this->definir($unidad);
        $destino = $this->definir($unidadStock);

        if ($origen['tipo'] !== $destino['tipo']) {
            throw ValidationException::withMessages([
                'ingredientes' => "No se puede convertir {$unidad} a {$unidadStock}.",
            ]);
        }

        if ($origen['tipo'] === 'conteo') {
            if (floor($cantidad) !== $cantidad) {
                throw ValidationException::withMessages([
                    'ingredientes' => 'Los ingredientes por unidad deben consumirse en cantidades enteras.',
                ]);
            }

            return $cantidad;
        }

        $cantidadBase = $cantidad * $origen['factor'];

        return round(
            $cantidadBase / $destino['factor'],
            3
        );
    }

    public function convertirABase(float $cantidad, string $unidad): array
    {
        $definicion = $this->definir($unidad);

        return [
            'tipo' => $definicion['tipo'],
            'cantidad' => $cantidad * $definicion['factor'],
        ];
    }

    private function definir(string $unidad): array
    {
        $unidad = Str::ascii(Str::lower(trim($unidad)));
        $unidad = preg_replace('/[^a-z]/', '', $unidad);

        $unidades = [
            'mg' => ['tipo' => 'peso', 'factor' => 1 / 28349.523125],
            'miligramo' => ['tipo' => 'peso', 'factor' => 1 / 28349.523125],
            'miligramos' => ['tipo' => 'peso', 'factor' => 1 / 28349.523125],
            'g' => ['tipo' => 'peso', 'factor' => 1 / 28.349523125],
            'gr' => ['tipo' => 'peso', 'factor' => 1 / 28.349523125],
            'gramo' => ['tipo' => 'peso', 'factor' => 1 / 28.349523125],
            'gramos' => ['tipo' => 'peso', 'factor' => 1 / 28.349523125],
            'kg' => ['tipo' => 'peso', 'factor' => 1000 / 28.349523125],
            'kilo' => ['tipo' => 'peso', 'factor' => 1000 / 28.349523125],
            'kilos' => ['tipo' => 'peso', 'factor' => 1000 / 28.349523125],
            'kilogramo' => ['tipo' => 'peso', 'factor' => 1000 / 28.349523125],
            'kilogramos' => ['tipo' => 'peso', 'factor' => 1000 / 28.349523125],
            'oz' => ['tipo' => 'peso', 'factor' => 1],
            'onza' => ['tipo' => 'peso', 'factor' => 1],
            'onzas' => ['tipo' => 'peso', 'factor' => 1],
            'lb' => ['tipo' => 'peso', 'factor' => 16],
            'lbs' => ['tipo' => 'peso', 'factor' => 16],
            'libra' => ['tipo' => 'peso', 'factor' => 16],
            'libras' => ['tipo' => 'peso', 'factor' => 16],
            'ml' => ['tipo' => 'volumen', 'factor' => 1],
            'mililitro' => ['tipo' => 'volumen', 'factor' => 1],
            'mililitros' => ['tipo' => 'volumen', 'factor' => 1],
            'l' => ['tipo' => 'volumen', 'factor' => 1000],
            'litro' => ['tipo' => 'volumen', 'factor' => 1000],
            'litros' => ['tipo' => 'volumen', 'factor' => 1000],
            'u' => ['tipo' => 'conteo', 'factor' => 1],
            'un' => ['tipo' => 'conteo', 'factor' => 1],
            'unidad' => ['tipo' => 'conteo', 'factor' => 1],
            'unidades' => ['tipo' => 'conteo', 'factor' => 1],
            'pieza' => ['tipo' => 'conteo', 'factor' => 1],
            'piezas' => ['tipo' => 'conteo', 'factor' => 1],
            'huevo' => ['tipo' => 'conteo', 'factor' => 1],
            'huevos' => ['tipo' => 'conteo', 'factor' => 1],
            'lata' => ['tipo' => 'conteo', 'factor' => 1],
            'latas' => ['tipo' => 'conteo', 'factor' => 1],
            'barra' => ['tipo' => 'conteo', 'factor' => 1],
            'barras' => ['tipo' => 'conteo', 'factor' => 1],
        ];

        if (!isset($unidades[$unidad])) {
            throw ValidationException::withMessages([
                'ingredientes' => "La unidad {$unidad} no está configurada.",
            ]);
        }

        return $unidades[$unidad];
    }
}