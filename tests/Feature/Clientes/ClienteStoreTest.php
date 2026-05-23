<?php

use App\Models\Cliente;
use App\Models\User;

use function Pest\Laravel\postJson;

test('usuario autenticado puede registrar cliente como  persona natural', function () {
    $user = User::factory()->create();
    $data = [
        'tipo_persona' => 'NATURAL',
        'nombre' => 'Juan Perez',
        'telefono' => '7855-7966',
        'correo' => 'correoprueba@gmail.com',
        'tipo_documento_receptor' => '13',
        'numero_documento' => '33423255',
    ];

    // Act - ejecutamos
    $response = $this->actingAs($user)
        ->postJson('api/clientes', $data);

    // Assert - verficamos
    $response->assertStatus(201);

    $this->assertDatabaseHas('clientes', [
        'nombre' => 'Juan Perez',
        'telefono' => '7855-7966',
        'registrado_por' => $user->id,
    ]);
});

test('usuario autenticado puede registrar una persona juridica', function () {
    $user = User::factory()->create();
    $data = [
        'tipo_persona' => 'JURIDICA',
        'razon_social' => 'Empresa SA',
        'nrc' => '123456-78',
        'giro_actividad' => 'Comercio',
        'telefono' => '7070-5678',
        'correo' => 'empresa@test.com',
        'tipo_documento_receptor' => '13',
        'numero_documento' => '43032542' 
    ];

    $response = $this->actingAs($user)
        ->postJson('api/clientes', $data);

    $response->assertStatus(201);

    $this->assertDatabaseHas('clientes', [
        'razon_social' => 'Empresa SA',
        'giro_actividad' => 'Comercio',
    ]);
});

test('Usuario no autenticado no puede registrar cliente', function(){
  $data = [
     'tipo_persona' => 'NATURAL',
     'nombre' => 'Omar perez'
  ];

   $response = postJson('api/clientes', $data);

   $response->assertStatus(401);
});

test('persona natural requiere campo nombre', function(){
    $user = User::factory()->create();
    $data = [
        'nombre' => 'omar',
        'razon_social' => 'No deberia pasar',
    ];

   $response = $this->actingAs($user)
   ->postJson('api/clientes',$data); 

   $response->assertStatus(422);
   $response->assertJsonValidationErrors(['nombre']);
});

test('persona juridica no debe enviar campo nombre', function () {
    $user = User::factory()->create();
    $data = [
        'tipo_persona' => 'JURIDICA',
        'razon_social' => 'Empresa SA',
        'nombre' => 'Esto no deberia estar', // Campo prohibido
        'nrc' => '12234578',
        'giro_actividad' => 'Comercio'
    ];
    
    $response = $this->actingAs($user)
        ->postJson('/api/clientes', $data);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['nombre']);
});


test('numero de documento debe ser unico por tipo de documento', function () {
    $user = User::factory()->create();
    
    // Crear primer cliente
    Cliente::factory()->create([
        'tipo_documento_receptor' => '13',
        'numero_documento' => '122443678',
        'registrado_por' => $user->id
    ]);
    
    // Intentar crear otro con mismo número y tipo
    $data = [
        'tipo_persona' => 'NATURAL',
        'nombre' => 'Otro Cliente',
        'tipo_documento_receptor' => '13',
        'numero_documento' => '12345678'
    ];
    
    $response = $this->actingAs($user)
        ->postJson('/api/clientes', $data);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['numero_documento']);
});
