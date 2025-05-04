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

    /**
     * Busca vehículos por marca y/o modelo
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $query = Vehiculo::query();

            // Si se proporciona un término de búsqueda general
            if ($request->has('search')) {
                $searchTerm = $request->search;
                
                $query->where(function($q) use ($searchTerm) {
                    $q->where('marca', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('modelo', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Si se proporciona específicamente una marca
            if ($request->has('marca')) {
                $query->where('marca', 'LIKE', "%{$request->marca}%");
            }

            // Si se proporciona específicamente un modelo
            if ($request->has('modelo')) {
                $query->where('modelo', 'LIKE', "%{$request->modelo}%");
            }

            $vehiculos = $query->with(['categoria', 'imagenes'])
                             ->orderBy('created_at', 'desc')
                             ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $vehiculos,
                'message' => 'Búsqueda realizada con éxito'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar la búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filtra vehículos según múltiples criterios
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filter(Request $request)
    {
        try {
            $query = Vehiculo::query();

            // Filtro por rango de precio
            if ($request->has('precio_min') && $request->has('precio_max')) {
                $query->whereBetween('precio', [$request->precio_min, $request->precio_max]);
            } elseif ($request->has('precio_min')) {
                $query->where('precio', '>=', $request->precio_min);
            } elseif ($request->has('precio_max')) {
                $query->where('precio', '<=', $request->precio_max);
            }

            // Filtro por año
            if ($request->has('anio_min') && $request->has('anio_max')) {
                $query->whereBetween('anio', [$request->anio_min, $request->anio_max]);
            } elseif ($request->has('anio_min')) {
                $query->where('anio', '>=', $request->anio_min);
            } elseif ($request->has('anio_max')) {
                $query->where('anio', '<=', $request->anio_max);
            }

            // Filtro por kilometraje máximo
            if ($request->has('kilometraje_max')) {
                $query->where('kilometraje', '<=', $request->kilometraje_max);
            }

            // Filtro por estado (nuevo/usado)
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            // Filtro por transmisión
            if ($request->has('transmision')) {
                $query->where('transmision', $request->transmision);
            }

            // Filtro por tipo de combustible
            if ($request->has('tipo_combustible')) {
                $query->where('tipo_combustible', $request->tipo_combustible);
            }

            // Filtro por marca
            if ($request->has('marca')) {
                $query->where('marca', 'LIKE', "%{$request->marca}%");
            }

            // Filtro por modelo
            if ($request->has('modelo')) {
                $query->where('modelo', 'LIKE', "%{$request->modelo}%");
            }

            // Filtro por color
            if ($request->has('color')) {
                $query->where('color', 'LIKE', "%{$request->color}%");
            }

            // Filtro por ubicación
            if ($request->has('ubicacion')) {
                $query->where('ubicacion', 'LIKE', "%{$request->ubicacion}%");
            }

            // Filtro por número de puertas
            if ($request->has('numero_puertas')) {
                $query->where('numero_puertas', $request->numero_puertas);
            }

            // Filtro por vehículo libre de accidentes
            if ($request->has('vehiculo_libre_accidentes')) {
                $query->where('vehiculo_libre_accidentes', $request->vehiculo_libre_accidentes);
            }

            $vehiculos = $query->with(['categoria', 'imagenes'])
                             ->orderBy('created_at', 'desc')
                             ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $vehiculos,
                'message' => 'Filtrado realizado con éxito'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar el filtrado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene todos los vehículos de un usuario específico junto con su información completa
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserVehiclesById($userId)
    {
        try {
            // Verificar si el usuario existe y obtener toda su información
            $user = \App\Models\User::select('id', 'name', 'email', 'rol', 'created_at', 'updated_at')
                ->find($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Obtener los vehículos del usuario con todas sus relaciones
            $vehiculos = Vehiculo::where('user_id', $userId)
                ->with([
                    'categoria',
                    'imagenes' => function($query) {
                        $query->orderBy('imagen_order', 'asc');
                    }
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            // Transformar las URLs de las imágenes para incluir la ruta completa y el base64
            $vehiculos->transform(function($vehiculo) {
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
                return $vehiculo;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'usuario' => [
                        'id' => $user->id,
                        'nombre' => $user->name,
                        'email' => $user->email,
                        'rol' => $user->rol,
                        'fecha_registro' => $user->created_at,
                        'ultima_actualizacion' => $user->updated_at
                    ],
                    'vehiculos' => $vehiculos,
                    'total_vehiculos' => $vehiculos->count()
                ],
                'message' => 'Información del usuario y vehículos obtenida exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la información: ' . $e->getMessage()
            ], 500);
        }
    }
}
