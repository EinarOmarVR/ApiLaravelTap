<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perfiles;
use App\Models\Permisos;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends Controller
{
    protected function TienePermiso(string $codigoPermiso)
    {
        try {

            $usuario = JWTAuth::parseToken()->authenticate();

        } catch (TokenExpiredException $e) {

            return response()->json([
                'message' => 'La sesión ha expirado. Inicie sesión nuevamente.'
            ], Response::HTTP_UNAUTHORIZED);

        } catch (TokenInvalidException $e) {

            return response()->json([
                'message' => 'El token es inválido.'
            ], Response::HTTP_UNAUTHORIZED);

        } catch (JWTException $e) {

            return response()->json([
                'message' => 'Usuario no autenticado.'
            ], Response::HTTP_UNAUTHORIZED);

        }

        if (!$usuario->SIDPerfil) {

            return response()->json([
                'message' => 'El usuario no tiene un perfil asignado.'
            ], Response::HTTP_FORBIDDEN);

        }

        $perfil = Perfiles::find($usuario->SIDPerfil);

        if (!$perfil) {

            return response()->json([
                'message' => 'El perfil del usuario no existe.'
            ], Response::HTTP_FORBIDDEN);

        }

        if (empty($perfil->APermisos)) {

            return response()->json([
                'message' => 'El perfil no tiene permisos asignados.'
            ], Response::HTTP_FORBIDDEN);

        }

        foreach ($perfil->APermisos as $idPermiso) {

            $permiso = Permisos::find($idPermiso);

            if ($permiso && $permiso->SCodigo === $codigoPermiso) {

                return true;

            }

        }

        return response()->json([
            'message' => 'No tiene permisos para realizar esta acción.'
        ], Response::HTTP_FORBIDDEN);
    }
}