<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Categoria>
 */
class CategoriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->randomElement([
                'SUV', 'Sedán', 'Coupé', 'Hatchback', 'Convertible', 'Pickup',
                'Deportivo', 'Furgoneta', 'Eléctrico', 'Híbrido', '4x4'
            ]),
        ];
    }
}
