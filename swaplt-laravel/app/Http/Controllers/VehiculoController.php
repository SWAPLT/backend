<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class VehiculoController extends Controller
{
    // Crear un vehículo
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'categoria_id' => 'required|exists:categorias,id',
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'anio' => 'required|integer',
            'estado' => 'required|in:nuevo,usado',
            'transmision' => 'required|string|max:255',
            'tipo_combustible' => 'required|string|max:255',
            'kilometraje' => 'required|integer',
            'fuerza' => 'required|integer',
            'capacidad_motor' => 'required|numeric',
            'color' => 'required|string|max:255',
            'ubicacion' => 'required|string|max:255',
            'matricula' => 'required|string|max:255',
            'numero_serie' => 'required|string|max:255',
            'numero_puertas' => 'required|integer',
            'descripcion' => 'required|string|max:255',
            'vehiculo_robado' => 'required|string|max:255',
            'vehiculo_libre_accidentes' => 'required|string|max:255',
        ]);

        $vehiculo = Vehiculo::create($request->all());

        return response()->json([
            'message' => 'Vehículo creado con éxito',
            'vehiculo' => $vehiculo
        ], 201);
    }

    // Obtener todos los vehículos
    public function index()
    {
        $vehiculos = Vehiculo::paginate(10);
        return response()->json($vehiculos);
    }

    // Obtener un vehículo específico
    public function show($id)
    {
        $vehiculo = Vehiculo::with(['categoria', 'imagenes'])->find($id);

        if (!$vehiculo) {
            return response()->json(['message' => 'Vehículo no encontrado'], 404);
        }

        // Transformar las URLs de las imágenes para incluir la ruta completa y el base64
        $vehiculo->imagenes->transform(function($imagen) {
            $path = storage_path('app/public/' . $imagen->imagen_url);
            $base64 = '';
            
            if (file_exists($path)) {
                $type = mime_content_type($path);
                $base64 = 'data:' . $type . ';base64,' . base64_encode(file_get_contents($path));
            }

            return [
                'id' => $imagen->id,
                'url' => route('vehiculo.imagen', ['id' => $imagen->id]),
                'orden' => $imagen->imagen_order,
                'vehiculo_id' => $imagen->vehiculo_id,
                'preview_url' => $base64
            ];
        });

        return response()->json([
            'vehiculo' => $vehiculo,
            'mensaje' => 'Vehículo encontrado con éxito'
        ]);
    }

    // Actualizar un vehículo
    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'categoria_id' => 'required|exists:categorias,id',
            'marca' => 'required|string|max:255',
            'modelo' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'anio' => 'required|integer',
            'estado' => 'required|in:nuevo,usado',
            'transmision' => 'required|string|max:255',
            'tipo_combustible' => 'required|string|max:255',
            'kilometraje' => 'required|integer',
            'fuerza' => 'required|integer',
            'capacidad_motor' => 'required|numeric',
            'color' => 'required|string|max:255',
            'ubicacion' => 'required|string|max:255',
            'matricula' => 'required|string|max:255',
            'numero_serie' => 'required|string|max:255',
            'numero_puertas' => 'required|integer',
            'descripcion' => 'required|string|max:255',
            'vehiculo_robado' => 'required|string|max:255',
            'vehiculo_libre_accidentes' => 'required|string|max:255',
        ]);

        $vehiculo = Vehiculo::find($id);

        if (!$vehiculo) {
            return response()->json(['message' => 'Vehículo no encontrado'], 404);
        }

        // Actualiza los datos del vehículo
        $vehiculo->update($request->all());

        return response()->json([
            'message' => 'Vehículo actualizado con éxito',
            'vehiculo' => $vehiculo
        ], 200);
    }


    // Eliminar un vehículo
    public function destroy($id)
    {
        $vehiculo = Vehiculo::find($id);

        if (!$vehiculo) {
            return response()->json(['message' => 'Vehículo no encontrado'], 404);
        }

        $vehiculo->delete();

        return response()->json(['message' => 'Vehículo eliminado con éxito'], 200);
    }

    public function misVehiculos()
    {
        // Obtener el ID del usuario autenticado
        $userId = Auth::id();

        // Buscar los vehículos donde el user_id coincide con el ID del usuario autenticado
        $vehiculos = Vehiculo::where('user_id', $userId)->get();

        return response()->json($vehiculos);
    }

    /**
     * Obtiene todos los vehículos del usuario autenticado mediante JWT
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserVehicles()
    {
        try {
            // Obtener el usuario autenticado mediante JWT
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Obtener los vehículos del usuario
            $vehiculos = Vehiculo::where('user_id', $user->id)
                ->with(['categoria', 'imagenes']) // Incluimos relaciones útiles
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $vehiculos,
                'message' => 'Vehículos obtenidos exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los vehículos: ' . $e->getMessage()
            ], 500);
        }
    }
}
