<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    // Registro de usuario
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $verificationCode = bin2hex(random_bytes(16)); // Código de verificación único

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'verification_code' => $verificationCode,
        ]);

        // Enviar correo de verificación
        $this->sendVerificationEmail($user);

        return response()->json([
            'message' => 'Usuario registrado con éxito. Verifica tu correo.',
            'user' => $user
        ]);
    }

    // Enviar correo de verificación
    public function sendVerificationEmail($user)
    {
        // Crear la URL con el código de verificación
        $verificationUrl = url("/api/verify-email/{$user->verification_code}");

        // Crear el cuerpo del correo
        $emailBody = "
        <html>
        <head>
            <title>Verifica tu correo</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; text-align: center; padding: 20px; }
                .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
                .btn { display: inline-block; padding: 10px 20px; margin-top: 20px; color: white; background-color: #007BFF; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Hola {$user->name},</h2>
                <p>Haz clic en el siguiente botón para verificar tu correo:</p>
                <a href='{$verificationUrl}' class='btn'>Verificar Correo</a> <!-- Enlace de verificación corregido -->
                <p>Si no solicitaste esto, ignora el correo.</p>
            </div>
        </body>
        </html>
    ";

        // Enviar el correo
        Mail::to($user->email)->send(new class($emailBody) extends \Illuminate\Mail\Mailable {
            public $emailBody;

            public function __construct($emailBody)
            {
                $this->emailBody = $emailBody;
            }

            public function build()
            {
                return $this->subject('Verifica tu correo')
                    ->html($this->emailBody);
            }
        });
    }


    // Verificar email
    public function verifyEmail($verification_code)
    {
        $user = User::where('verification_code', $verification_code)->first();

        if (!$user) {
            return response()->json(['error' => 'Código inválido'], 400);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Correo verificado con éxito.']);
    }

    // Iniciar sesión
    public function login(Request $request)
    {
        // Validación de los datos de entrada
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Verificar si el email existe en la base de datos
        $user = User::where('email', $request->email)->first();

        // Verificar si el usuario existe y si la contraseña coincide con el hash
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }

        // Generar el token JWT
        if ($token = JWTAuth::fromUser($user)) {
            return response()->json(['token' => $token]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }


    // Obtener usuario autenticado
    public function me()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token inválido'], 401);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'rol' => $user->rol, // Asegúrate de devolver el rol aquí
            'email_verified_at' => $user->email_verified_at,
        ]);
    }

    // Cerrar sesión
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Sesión cerrada con éxito']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cerrar sesión'], 500);
        }
    }

    // Refrescar token
    public function refreshToken(Request $request)
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();
            return response()->json(['token' => $newToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudo refrescar el token'], 500);
        }
    }

    // Metodo protegido: solo Admins pueden acceder
    public function adminAction()
    {
        $user = auth()->user();

        if ($user->rol !== 'admin') {
            return response()->json(['error' => 'Acceso denegado, solo Admins pueden realizar esta acción'], 403);
        }

        return response()->json(['message' => 'Acción de administrador realizada']);
    }

    // Solicitar restablecimiento de contraseña
    public function requestPasswordReset(Request $request)
    {
        // Validar que el correo electrónico sea válido
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'El correo no es válido o no existe.'], 400);
        }

        // Buscar el usuario por su correo
        $user = User::where('email', $request->email)->first();

        // Generar un token de restablecimiento único
        $resetToken = bin2hex(random_bytes(16));

        // Guardar el token en el usuario (o en una tabla de restablecimiento si prefieres)
        $user->reset_token = $resetToken;
        $user->save();

        // Enviar el correo con el enlace de restablecimiento
        $this->sendPasswordResetEmail($user, $resetToken);

        return response()->json(['message' => 'Se ha enviado un correo para restablecer tu contraseña.']);
    }

    public function sendPasswordResetEmail($user, $resetToken)
    {
        $resetUrl = url("/api/password/reset/{$resetToken}");

        $emailBody = "
        <html>
        <head>
            <title>Restablecer tu contraseña</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; text-align: center; padding: 20px; }
                .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
                .btn { display: inline-block; padding: 10px 20px; margin-top: 20px; color: white; background-color: #007BFF; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Hola {$user->name},</h2>
                <p>Haz clic en el siguiente botón para restablecer tu contraseña:</p>
                <a href='{$resetUrl}' class='btn'>Restablecer Contraseña</a>
                <p>Si no solicitaste esto, ignora el correo.</p>
            </div>
        </body>
        </html>
    ";

        Mail::to($user->email)->send(new class($emailBody) extends \Illuminate\Mail\Mailable {
            public $emailBody;

            public function __construct($emailBody)
            {
                $this->emailBody = $emailBody;
            }

            public function build()
            {
                return $this->subject('Restablecer tu contraseña')
                    ->html($this->emailBody);
            }
        });
    }

    public function resetPassword(Request $request, $token)
    {
        // Validar las contraseñas
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Las contraseñas no coinciden o son demasiado cortas.'], 400);
        }

        // Buscar al usuario con el token de restablecimiento
        $user = User::where('reset_token', $token)->first();

        if (!$user) {
            return response()->json(['error' => 'Token inválido o ha expirado.'], 400);
        }

        // Actualizar la contraseña del usuario
        $user->password = Hash::make($request->password);
        $user->reset_token = null; // Limpiar el token de restablecimiento
        $user->save();

        return response()->json(['message' => 'Contraseña restablecida con éxito.']);
    }

    public function updateProfile(Request $request)
    {
        // Validación de los datos de entrada
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . auth()->id(),  // Verifica que el correo no esté duplicado
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Obtener el usuario autenticado
        $user = auth()->user();

        // Si se ha proporcionado un nuevo nombre, actualizamos
        if ($request->has('name')) {
            $user->name = $request->name;
        }

        // Si se ha proporcionado un nuevo correo, actualizamos
        if ($request->has('email')) {
            $user->email = $request->email;
            $user->verification_code = bin2hex(random_bytes(16)); // Generar un nuevo código de verificación
            $user->email_verified_at = null; // Eliminar la verificación anterior
        }

        // Guardamos el usuario actualizado
        $user->save();

        // Enviar un nuevo correo de verificación al usuario
        $this->sendVerificationEmail($user);

        return response()->json([
            'message' => 'Perfil actualizado con éxito. Verifica tu correo.',
            'user' => $user,
        ]);
    }

}
