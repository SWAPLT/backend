<?php

// app/Http/Controllers/VehiculoImagenController.php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\VehiculoImagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VehiculoImagenController extends Controller
{
    // Función para subir imágenes de un vehículo
    public function store(Request $request, $vehiculoId)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $vehiculo = Vehiculo::find($vehiculoId);
        if (!$vehiculo) {
            return response()->json(['message' => 'Vehículo no encontrado'], 404);
        }

        $imagePath = $request->file('imagen')->store('vehiculos_imagenes', 'public');

        $vehiculoImagen = new VehiculoImagen();
        $vehiculoImagen->vehiculo_id = $vehiculoId;
        $vehiculoImagen->imagen_url = $imagePath;
        $vehiculoImagen->imagen_path = Storage::disk('public')->path($imagePath);
        $vehiculoImagen->imagen_order = $request->input('imagen_order') ?? 1; // ✅ Valor por defecto si no se envía
        $vehiculoImagen->save();

        return response()->json(['message' => 'Imagen subida con éxito', 'imagen' => $vehiculoImagen], 201);
    }

    // Función para obtener las imágenes de un vehículo
    public function show($vehiculoId)
    {
        $vehiculo = Vehiculo::find($vehiculoId);
        if (!$vehiculo) {
            return response()->json(['message' => 'Vehículo no encontrado'], 404);
        }

        $imagenes = $vehiculo->imagenes; // Obtener las imágenes asociadas al vehículo (relación definida en el modelo Vehiculo)

        return response()->json($imagenes);
    }

    // Función para eliminar una imagen de un vehículo
    public function destroy($vehiculoId, $imagenId)
    {
        // Obtener el vehículo
        $vehiculo = Vehiculo::find($vehiculoId);
        if (!$vehiculo) {
            return response()->json(['message' => 'Vehículo no encontrado'], 404);
        }

        // Obtener la imagen a eliminar
        $vehiculoImagen = VehiculoImagen::find($imagenId);
        if (!$vehiculoImagen) {
            return response()->json(['message' => 'Imagen no encontrada'], 404);
        }

        // Eliminar la imagen del almacenamiento
        Storage::disk('public')->delete($vehiculoImagen->imagen_url);

        // Eliminar el registro de la base de datos
        $vehiculoImagen->delete();

        return response()->json(['message' => 'Imagen eliminada con éxito']);
    }
}
