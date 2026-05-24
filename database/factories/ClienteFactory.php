<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Alternar entre persona natural y jurídica
        $tipoPersona = $this->faker->randomElement(['NATURAL', 'JURIDICA']);

        if ($tipoPersona === 'NATURAL') {
            return [
                'tipo_persona' => 'NATURAL',
                'nombre' => $this->faker->name(),
                'razon_social' => null,
                'tipo_documento_receptor' => $this->faker->randomElement(['13', '36', '02', '03', null]),
                'numero_documento' => $this->faker->optional()->numerify('########'),
                'telefono' => $this->faker->optional()->phoneNumber(),
                'correo' => $this->faker->optional()->safeEmail(),
                'nrc' => null,
                'giro_actividad' => null,
                'registrado_por' => 1, 
                'activo' => true,
            ];
        } else {
            return [
                'tipo_persona' => 'JURIDICA',
                'nombre' => null,
                'razon_social' => $this->faker->company(),
                'tipo_documento_receptor' => $this->faker->randomElement(['13', '36', '02', '03', null]),
                'numero_documento' => $this->faker->optional()->numerify('########'),
                'telefono' => $this->faker->optional()->phoneNumber(),
                'correo' => $this->faker->optional()->companyEmail(),
                'nrc' => $this->faker->optional()->numerify('######-#'),
                'giro_actividad' => $this->faker->optional()->word(),
                'registrado_por' => 1, // Se sobreescribirá al crear
                'activo' => true,
            ];
        }
    }
}
