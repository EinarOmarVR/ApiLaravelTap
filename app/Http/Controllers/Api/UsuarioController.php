<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\Perfiles;
use App\Models\Usuarios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UsuarioController extends Controller
{
    private function ValidarUsuario(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'SNombre' => 'required|string|max:255',

            'SUsuario' => 'required|email|max:255',

            'SPassword' => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',        // Minúscula
                'regex:/[A-Z]/',        // Mayúscula
                'regex:/[0-9]/',        // Número
                'regex:/[@$!%*?&#]/'    // Carácter especial
            ],

            'STelefono' => 'nullable|string|max:20',

            'SFotoPerfil' => 'required|image|mimes:jpg,jpeg,png|max:2048',

        ], [

            // Nombre
            'SNombre.required' => 'El nombre es obligatorio.',
            'SNombre.string' => 'El nombre debe ser texto.',
            'SNombre.max' => 'El nombre no puede superar los 255 caracteres.',


            // Usuario
            'SUsuario.required' => 'El usuario es obligatorio.',
            'SUsuario.email' => 'Debe ingresar un correo electrónico válido.',
            'SUsuario.max' => 'El usuario no puede superar los 255 caracteres.',


            // Contraseña
            'SPassword.required' => 'La contraseña es obligatoria.',
            'SPassword.string' => 'La contraseña debe ser texto.',
            'SPassword.min' => 'La contraseña debe tener mínimo 8 caracteres.',
            'SPassword.regex' => 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un carácter especial.',


            // Teléfono
            'STelefono.string' => 'El teléfono debe ser texto.',
            'STelefono.max' => 'El teléfono no puede superar los 20 caracteres.',


            // Imagen
            'SFotoPerfil.required' => 'La foto de perfil es obligatoria.',
            'SFotoPerfil.image' => 'El archivo debe ser una imagen.',
            'SFotoPerfil.mimes' => 'La imagen debe ser formato JPG, JPEG o PNG.',
            'SFotoPerfil.max' => 'La imagen no debe superar los 2 MB.',

        ]);


        if ($validator->fails()) {

            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        return null;
    }
    public function GetUsuarios()
    {
        $usuarios = Usuarios::orderBy('TFechaCap', 'desc')->get();

        return UsuarioResource::collection($usuarios);
    }
    public function GetUsuario(string $id)
    {
        $usuario = Usuarios::find($id);

        if (!$usuario) {

            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], Response::HTTP_NOT_FOUND);

        }

        return new UsuarioResource($usuario);
    }
    public function InsertUsuario(Request $request)
    {
        $error = $this->ValidarUsuario($request);

        if ($error) {
            return $error;
        }
        // Validar correo único
        if (Usuarios::where('SUsuario', $request->SUsuario)->exists()) {

            return response()->json([
                'message' => 'Ya existe un usuario con ese correo.'
            ], Response::HTTP_CONFLICT);

        }
          // Crear usuario
        $usuario = new Usuarios();
        $usuario->SCodigo = $this->GenerarCodigoUsuario();
        $usuario->SNombre = $request->SNombre;
        $usuario->SUsuario = $request->SUsuario;
        $usuario->SPassword = Hash::make($request->SPassword);
        $usuario->BPasswordTemporal = false;
        $usuario->TFechaPasswordTemporal = null;
        $usuario->STelefono = $request->STelefono;

 
        $SIDPerfil = null;
        // Si viene perfil desde el formulario
        if ($request->filled('SIDPerfil')) {

            $perfil = Perfiles::find($request->SIDPerfil);
            if (!$perfil) {

                return response()->json([
                    'message' => 'El perfil seleccionado no existe.'
                ], Response::HTTP_BAD_REQUEST);
            }
            $SIDPerfil = $perfil->_id;
        } else {
            // Buscar perfil por defecto
            $perfilDefault = Perfiles::where(
                'SCodigo',
                'PER_LecArt'
            )->first();
            if ($perfilDefault) {

                $SIDPerfil = $perfilDefault->_id;
            }
        }
        $usuario->SIDPerfil = $SIDPerfil;
    
        if ($request->hasFile('SFotoPerfil')) {


            $ruta = Storage::disk('public')->putFileAs(
                'Usuarios',
                $request->file('SFotoPerfil'),
                $usuario->SCodigo . '.' .
                $request->file('SFotoPerfil')->extension()
            );
            $usuario->SFotoPerfil = $ruta;

        }
        $usuario->save();
        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data' => $usuario
        ], Response::HTTP_CREATED);
    }
    public function UpdateUsuario(Request $request, string $id)
    {
        $error = $this->ValidarUsuario($request);

        if ($error) {
            return $error;
        }

        $usuario = Usuarios::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        $existeCorreo = Usuarios::where('SUsuario', $request->SUsuario)
            ->where('_id', '!=', $id)
            ->exists();

        if ($existeCorreo) {
            return response()->json([
                'message' => 'Ya existe un usuario con ese correo.'
            ], Response::HTTP_CONFLICT);
        }

        if (!Perfiles::find($request->SIDPerfil)) {
            return response()->json([
                'message' => 'El perfil seleccionado no existe.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $usuario->SNombre = $request->SNombre;
        $usuario->SUsuario = $request->SUsuario;
        $usuario->STelefono = $request->STelefono;
        $usuario->SIDPerfil = $request->SIDPerfil;

        if ($request->filled('SPassword')) {
            $usuario->SPassword = Hash::make($request->SPassword);
        }

        // Si llega una nueva imagen
        if ($request->hasFile('SFotoPerfil')) {

            // Eliminar la imagen anterior
            if ($usuario->SFotoPerfil &&
                Storage::disk('public')->exists($usuario->SFotoPerfil)) {

                Storage::disk('public')->delete($usuario->SFotoPerfil);
            }

            // Guardar la nueva imagen
            if ($request->hasFile('SFotoPerfil')) {

                $ruta = Storage::disk('public')->putFileAs(
                        'Usuarios',
                        $request->file('SFotoPerfil'),
                        $usuario->SCodigo.'.jpg'
                    );
                $usuario->SFotoPerfil = $ruta;
            }
        }

        $usuario->save();

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data' => $usuario
        ], Response::HTTP_OK);
    }



  public function DeleteUsuario(string $id)
    {
        $usuario = Usuarios::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }


        // Eliminar foto del storage
        if ($usuario->SFotoPerfil &&
            Storage::disk('public')->exists($usuario->SFotoPerfil)) {

            Storage::disk('public')->delete($usuario->SFotoPerfil);
        }


        // Eliminar usuario de MongoDB
        $usuario->delete();


        return response()->json([
            'message' => 'Usuario eliminado correctamente.'
        ], Response::HTTP_OK);
    }


    /**
     * Generar código automático
     */
    private function GenerarCodigoUsuario()
    {
        $ultimoUsuario = Usuarios::orderBy('SCodigo', 'desc')->first();

        if (!$ultimoUsuario) {
            return 'USR00001';
        }

        $numero = (int) substr($ultimoUsuario->SCodigo, 3);

        return 'USR' . str_pad($numero + 1, 5, '0', STR_PAD_LEFT);
    }
}