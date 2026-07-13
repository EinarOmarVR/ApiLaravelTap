<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PermisoResource;
use App\Models\Permisos;

class PermisosController extends BaseController
{
    

    /**
     * Validar permiso
     */
    private function ValidarPermiso(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'SCodigo' => 'required|string|max:100',
            'SPermiso' => 'required|string|max:255',
            'SModulo' => 'required|string|max:255',
            'SDescripcion' => 'nullable|string|max:500'
        ], [
            'SCodigo.required' => 'El código del permiso es obligatorio.',
            'SCodigo.string' => 'El código del permiso debe ser texto.',

            'SPermiso.required' => 'El nombre del permiso es obligatorio.',
            'SPermiso.string' => 'El nombre del permiso debe ser texto.',

            'SModulo.required' => 'El módulo es obligatorio.',
            'SModulo.string' => 'El módulo debe ser texto.',

            'SDescripcion.string' => 'La descripción debe ser texto.'
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
     * Obtener todos los permisos
     */
    public function GetPermisos()
    {
        $permiso = $this->TienePermiso('PERMISSION_VIEW');

        if ($permiso !== true) {
            return $permiso;
        }
        
        $permisos = Permisos::orderBy('TFechaCap', 'desc')->get();

        return PermisoResource::collection($permisos);
    }

    /**
     * Obtener un permiso por Id
     */
    public function GetPermiso(string $id)
    {
        
        $permiso = $this->TienePermiso('PERMISSION_VIEW');

        if ($permiso !== true) {
            return $permiso;
        }

        $permiso = Permisos::find($id);

        if (!$permiso) {
            return response()->json([
                'message' => 'Permiso no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        return new PermisoResource($permiso);
    }

    /**
     * Crear permiso
     */
    public function InsertPermiso(Request $request)
    {
        $permiso = $this->TienePermiso('PERMISSION_ADDUPD');

        if ($permiso !== true) {
            return $permiso;
        }
        $error = $this->ValidarPermiso($request);

        if ($error) {
            return $error;
        }

        if (Permisos::where('SCodigo', $request->SCodigo)->exists()) {
            return response()->json([
                'message' => 'Ya existe un permiso con ese código.'
            ], Response::HTTP_CONFLICT);
        }

        $permiso = new Permisos();

        $permiso->SCodigo = $request->SCodigo;
        $permiso->SPermiso = $request->SPermiso;
        $permiso->SModulo = $request->SModulo;
        $permiso->SDescripcion = $request->SDescripcion;

        $permiso->save();

        return response()->json([
            'message' => 'Permiso creado correctamente.',
            'data' => $permiso
        ], Response::HTTP_CREATED);
    }

    /**
     * Actualizar permiso
     */
    public function UpdatePermiso(Request $request, string $id)
    {
        $permiso = $this->TienePermiso('PERMISSION_ADDUPD');

        if ($permiso !== true) {
            return $permiso;
        }
        $error = $this->ValidarPermiso($request);

        if ($error) {
            return $error;
        }

        $permiso = Permisos::find($id);

        if (!$permiso) {
            return response()->json([
                'message' => 'Permiso no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        $existeCodigo = Permisos::where('SCodigo', $request->SCodigo)
            ->where('_id', '!=', $id)
            ->exists();

        if ($existeCodigo) {
            return response()->json([
                'message' => 'Ya existe un permiso con ese código.'
            ], Response::HTTP_CONFLICT);
        }

        $permiso->SCodigo = $request->SCodigo;
        $permiso->SPermiso = $request->SPermiso;
        $permiso->SModulo = $request->SModulo;
        $permiso->SDescripcion = $request->SDescripcion;

        $permiso->save();

        return response()->json([
            'message' => 'Permiso actualizado correctamente.',
            'data' => $permiso
        ], Response::HTTP_OK);
    }

    /**
     * Eliminar permiso
     */
    public function DeletePermiso(string $id)
    {
        $permiso = $this->TienePermiso('PERMISSION_ADDUPD');

        if ($permiso !== true) {
            return $permiso;
        }
        $permiso = Permisos::find($id);

        if (!$permiso) {
            return response()->json([
                'message' => 'Permiso no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        $permiso->delete();

        return response()->json([
            'message' => 'Permiso eliminado correctamente.'
        ], Response::HTTP_OK);
    }
}