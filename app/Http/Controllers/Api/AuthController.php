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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
        $validator = Validator::make($request->all(), [

            'SUsuario' => 'required|email'

        ], [

            'SUsuario.required' => 'El correo es obligatorio.',
            'SUsuario.email' => 'Debe ingresar un correo válido.'

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
                'message' => 'No existe un usuario con ese correo.'
            ], Response::HTTP_NOT_FOUND);

        }

        /*
        |--------------------------------------------------------------------------
        | Generar contraseña temporal
        |--------------------------------------------------------------------------
        */

        $passwordTemporal = Str::password(
            10,   // Longitud
            true, // Letras
            true, // Números
            true, // Símbolos
            false // Espacios
        );

        /*
        |--------------------------------------------------------------------------
        | Guardar contraseña temporal
        |--------------------------------------------------------------------------
        */

        $usuario->SPassword = Hash::make($passwordTemporal);

        $usuario->BPasswordTemporal = true;

        $usuario->TFechaPasswordTemporal = now();

        $usuario->save();

        /*
        |--------------------------------------------------------------------------
        | Enviar correo
        |--------------------------------------------------------------------------
        */

        Mail::raw(

            "Hola {$usuario->SNombre}
            Se solicitó recuperar la contraseña de tu cuenta.
            Tu contraseña temporal es:
            {$passwordTemporal}
            Por seguridad, al iniciar sesión deberás cambiarla.",
            function ($message) use ($usuario) {
                $message->to($usuario->SUsuario)
                    ->subject('Recuperación de contraseña');
            }

        );
        return response()->json([
            'message' => 'Se envió una contraseña temporal al correo registrado.'
        ], Response::HTTP_OK);
    }
    public function CambiarPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'SPassword' => [
                'required',
                'confirmed',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*?&#]/'
            ]

        ], [

            'SPassword.required' => 'La contraseña es obligatoria.',
            'SPassword.confirmed' => 'La confirmación de la contraseña no coincide.',
            'SPassword.min' => 'La contraseña debe tener mínimo 8 caracteres.',
            'SPassword.regex' => 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un carácter especial.'

        ]);

        if ($validator->fails()) {

            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        /*
        |--------------------------------------------------------------------------
        | Usuario autenticado
        |--------------------------------------------------------------------------
        */

        $usuario = JWTAuth::parseToken()->authenticate();

        if (!$usuario) {

            return response()->json([
                'message' => 'Usuario no autenticado.'
            ], Response::HTTP_UNAUTHORIZED);

        }

        /*
        |--------------------------------------------------------------------------
        | Actualizar contraseña
        |--------------------------------------------------------------------------
        */

        $usuario->SPassword = Hash::make($request->SPassword);

        $usuario->BPasswordTemporal = false;

        $usuario->TFechaPasswordTemporal = null;

        $usuario->save();

        return response()->json([

            'message' => 'Contraseña actualizada correctamente.'

        ], Response::HTTP_OK);
    }
}
