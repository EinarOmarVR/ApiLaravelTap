<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perfiles;
use App\Models\Permisos;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends Controller
{
    protected function TienePermiso(string $codigoPermiso)
    {
        try {

            $usuario = JWTAuth::parseToken()->authenticate();

        } catch (JWTException $e) {

            return response()->json([
                'message' => 'Usuario no autenticado.'
            ], Response::HTTP_UNAUTHORIZED);

        }

        $perfil = Perfiles::find($usuario->SIDPerfil);

        if (!$perfil) {

            return response()->json([
                'message' => 'El perfil del usuario no existe.'
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