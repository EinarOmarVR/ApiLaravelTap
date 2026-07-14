<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PerfilResource;
use App\Http\Resources\PermisoResource;
use App\Http\Resources\UsuarioResource;
use App\Models\Perfiles;
use App\Models\Permisos;
use App\Models\Usuarios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class AuthController extends Controller
{
    public function Login(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'SUsuario' => 'required|email',
            'SPassword' => 'required|string'

        ], [

            'SUsuario.required' => 'El usuario es obligatorio.',
            'SUsuario.email' => 'Debe ingresar un correo electrónico válido.',

            'SPassword.required' => 'La contraseña es obligatoria.'

        ]);

        if ($validator->fails()) {

            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        /*
        |--------------------------------------------------------------------------
        | Buscar usuario
        |--------------------------------------------------------------------------
        */

        $usuario = Usuarios::where(
            'SUsuario',
            $request->SUsuario
        )->first();

        if (!$usuario) {

            return response()->json([
                'message' => 'Usuario o contraseña incorrectos.'
            ], Response::HTTP_UNAUTHORIZED);

        }

        /*
        |--------------------------------------------------------------------------
        | Validar contraseña
        |--------------------------------------------------------------------------
        */

        if (!Hash::check(
            $request->SPassword,
            $usuario->SPassword
        )) {

            return response()->json([
                'message' => 'Usuario o contraseña incorrectos.'
            ], Response::HTTP_UNAUTHORIZED);

        }

        /*
        |--------------------------------------------------------------------------
        | Generar Token JWT
        |--------------------------------------------------------------------------
        */

        $token = JWTAuth::fromUser($usuario);

        /*
        |--------------------------------------------------------------------------
        | Obtener perfil
        |--------------------------------------------------------------------------
        */

        $perfil = null;
        $permisos = collect();

        if ($usuario->SIDPerfil) {

            $perfil = Perfiles::find($usuario->SIDPerfil);

            if ($perfil && !empty($perfil->APermisos)) {

                $permisos = Permisos::whereIn(
                    '_id',
                    $perfil->APermisos
                )->get();

            }

        }

        /*
        |--------------------------------------------------------------------------
        | ¿Debe cambiar la contraseña?
        |--------------------------------------------------------------------------
        */

        $requiereCambioPassword = (bool) ($usuario->BPasswordTemporal ?? false);

        return response()->json([

            'message' => $requiereCambioPassword
                ? 'Debe cambiar su contraseña.'
                : 'Inicio de sesión correcto.',

            'requiereCambioPassword' => $requiereCambioPassword,

            'token' => $token,

            'usuario' => new UsuarioResource($usuario),

            'perfil' => $perfil
                ? new PerfilResource($perfil)
                : null,

            'permisos' => PermisoResource::collection($permisos)

        ], Response::HTTP_OK);
    }
        
    public function Logout(Request $request)
    {
        try {

            JWTAuth::invalidate(
                JWTAuth::getToken()
            );

            return response()->json([
                'message' => 'Sesión cerrada correctamente.'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'No fue posible cerrar la sesión.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        }
    }
    public function RecuperarPassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'SUsuario' => 'required|email'
            ],
            [
                'SUsuario.required' => 'El correo es obligatorio.',
                'SUsuario.email' => 'Debe ingresar un correo válido.'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /*
        |--------------------------------------------------------------------------
        | Buscar usuario
        |--------------------------------------------------------------------------
        */

        $usuario = Usuarios::where(
            'SUsuario',
            $request->SUsuario
        )->first();

        if (!$usuario) {
            return response()->json([
                'message' => 'No existe un usuario con ese correo.'
            ], Response::HTTP_NOT_FOUND);
        }

        /*
        |--------------------------------------------------------------------------
        | Generar contraseña temporal
        |--------------------------------------------------------------------------
        */

        $passwordTemporal = Str::password(
            10,    // Longitud
            true,  // Letras
            true,  // Números
            true,  // Símbolos
            false  // Espacios
        );

        /*
        |--------------------------------------------------------------------------
        | Preparar contenido
        |--------------------------------------------------------------------------
        */

        $nombreSeguro = e($usuario->SNombre);
        $passwordSeguro = e($passwordTemporal);

        try {
            /*
            |--------------------------------------------------------------------------
            | Enviar correo mediante la API HTTPS de Brevo
            |--------------------------------------------------------------------------
            */

            $response = Http::timeout(30)
                ->withHeaders([
                    'api-key' => config('services.brevo.key'),
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => [
                        'name' => 'Sistema TAP',
                        'email' => env('MAIL_FROM_ADDRESS'),
                    ],
                    'to' => [
                        [
                            'email' => $usuario->SUsuario,
                            'name' => $usuario->SNombre,
                        ],
                    ],
                    'subject' => 'Recuperación de contraseña',
                    'htmlContent' => "
                        <h2>Recuperación de contraseña</h2>

                        <p>Hola {$nombreSeguro},</p>

                        <p>
                            Se solicitó recuperar la contraseña de tu cuenta.
                        </p>

                        <p>Tu contraseña temporal es:</p>

                        <p style=\"font-size: 18px;\">
                            <strong>{$passwordSeguro}</strong>
                        </p>

                        <p>
                            Por seguridad, al iniciar sesión deberás cambiarla.
                        </p>
                    ",
                ]);

            if ($response->failed()) {
                Log::error('Error al enviar correo mediante Brevo.', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return response()->json([
                    'message' => 'No fue posible enviar el correo de recuperación.'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            /*
            |--------------------------------------------------------------------------
            | Guardar contraseña después del envío exitoso
            |--------------------------------------------------------------------------
            */

            $usuario->SPassword = Hash::make($passwordTemporal);
            $usuario->BPasswordTemporal = true;
            $usuario->TFechaPasswordTemporal = now();
            $usuario->save();

            return response()->json([
                'message' => 'Se envió una contraseña temporal al correo registrado.'
            ], Response::HTTP_OK);

        } catch (\Throwable $exception) {
            Log::error('Excepción al enviar correo mediante Brevo.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'No fue posible enviar el correo de recuperación.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function CambiarPassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'SPassword' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/[0-9]/',
                    'regex:/[@$!%*?&#]/'
                ]
            ],
            [
                'SPassword.required' =>
                    'La nueva contraseña es obligatoria.',

                'SPassword.string' =>
                    'La contraseña debe ser texto.',

                'SPassword.min' =>
                    'La contraseña debe tener mínimo 8 caracteres.',

                'SPassword.confirmed' =>
                    'Las contraseñas no coinciden.',

                'SPassword.regex' =>
                    'La contraseña debe contener una mayúscula, una minúscula, un número y un carácter especial.'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /*
        |--------------------------------------------------------------------------
        | Obtener usuario autenticado
        |--------------------------------------------------------------------------
        */

        $usuarioAutenticado =
            JWTAuth::parseToken()->authenticate();

        if (!$usuarioAutenticado) {
            return response()->json([
                'message' => 'Usuario no autenticado.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        /*
        * Consultar explícitamente el modelo Usuarios
        * para que se reconozca el método save().
        */
        $usuario = Usuarios::find(
            $usuarioAutenticado->getAuthIdentifier()
        );

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        /*
        |--------------------------------------------------------------------------
        | Actualizar contraseña
        |--------------------------------------------------------------------------
        */

        $usuario->SPassword = Hash::make(
            $request->SPassword
        );

        $usuario->BPasswordTemporal = false;
        $usuario->TFechaPasswordTemporal = null;

        $usuario->save();

        /*
        * Invalidar el token actual para obligar al usuario
        * a iniciar sesión con la contraseña nueva.
        */
        JWTAuth::invalidate(
            JWTAuth::getToken()
        );

        return response()->json([
            'message' =>
                'Contraseña actualizada correctamente. Inicia sesión nuevamente.'
        ], Response::HTTP_OK);
    }
}
