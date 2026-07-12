<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PerfilResource;
use App\Models\Perfiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PerfilesController extends Controller
{
    /**
     * Validar perfil
     */
    private function ValidarPerfil(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'SCodigo' => 'required|string|max:100',
            'SPerfil' => 'required|string|max:255',
            'SDescripcion' => 'nullable|string|max:500',
            'APermisos' => 'nullable|array'
        ], [
            'SCodigo.required' => 'El código del perfil es obligatorio.',
            'SCodigo.string' => 'El código del perfil debe ser texto.',

            'SPerfil.required' => 'El nombre del perfil es obligatorio.',
            'SPerfil.string' => 'El nombre del perfil debe ser texto.',

            'SDescripcion.string' => 'La descripción debe ser texto.',

            'APermisos.array' => 'Los permisos deben ser un arreglo.'
        ]);


        if ($validator->fails()) {

            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        return null;
    }


    /**
     * Obtener todos los perfiles
     */
    public function GetPerfiles()
    {
        $perfiles = Perfiles::orderBy('TFechaCap', 'desc')->get();

        return PerfilResource::collection($perfiles);
    }


    /**
     * Obtener un perfil por Id
     */
    public function GetPerfil(string $id)
    {
        $perfil = Perfiles::find($id);

        if (!$perfil) {

            return response()->json([
                'message' => 'Perfil no encontrado.'
            ], Response::HTTP_NOT_FOUND);

        }


        return new PerfilResource($perfil);
    }


    /**
     * Crear perfil
     */
    public function InsertPerfil(Request $request)
    {
        $error = $this->ValidarPerfil($request);

        if ($error) {
            return $error;
        }


        if (Perfiles::where('SCodigo', $request->SCodigo)->exists()) {

            return response()->json([
                'message' => 'Ya existe un perfil con ese código.'
            ], Response::HTTP_CONFLICT);

        }


        $perfil = new Perfiles();


        $perfil->SCodigo = $request->SCodigo;
        $perfil->SPerfil = $request->SPerfil;
        $perfil->SDescripcion = $request->SDescripcion;
        $perfil->APermisos = $request->APermisos ?? [];
        $perfil->TFechaCap = now();


        $perfil->save();


        return response()->json([
            'message' => 'Perfil creado correctamente.',
            'data' => $perfil
        ], Response::HTTP_CREATED);
    }


    /**
     * Actualizar perfil
     */
    public function UpdatePerfil(Request $request, string $id)
    {
        $error = $this->ValidarPerfil($request);

        if ($error) {
            return $error;
        }


        $perfil = Perfiles::find($id);


        if (!$perfil) {

            return response()->json([
                'message' => 'Perfil no encontrado.'
            ], Response::HTTP_NOT_FOUND);

        }


        $existeCodigo = Perfiles::where('SCodigo', $request->SCodigo)
            ->where('_id', '!=', $id)
            ->exists();


        if ($existeCodigo) {

            return response()->json([
                'message' => 'Ya existe un perfil con ese código.'
            ], Response::HTTP_CONFLICT);

        }


        $perfil->SCodigo = $request->SCodigo;
        $perfil->SPerfil = $request->SPerfil;
        $perfil->SDescripcion = $request->SDescripcion;
        $perfil->APermisos = $request->APermisos ?? [];


        $perfil->save();


        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'data' => $perfil
        ], Response::HTTP_OK);
    }


    /**
     * Eliminar perfil
     */
    public function DeletePerfil(string $id)
    {
        $perfil = Perfiles::find($id);


        if (!$perfil) {

            return response()->json([
                'message' => 'Perfil no encontrado.'
            ], Response::HTTP_NOT_FOUND);

        }


        $perfil->delete();


        return response()->json([
            'message' => 'Perfil eliminado correctamente.'
        ], Response::HTTP_OK);
    }
}