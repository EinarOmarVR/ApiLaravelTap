<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UsuarioResource;
use App\Models\Perfiles;
use App\Models\Usuarios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class UsuarioController extends BaseController
{
    /**
     * Validar creación o edición administrativa.
     */
    private function ValidarUsuario(
        Request $request,
        bool $esActualizacion = false
    ) {
        $validator = Validator::make(
            $request->all(),
            [
                'SNombre' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'SUsuario' => [
                    'required',
                    'email',
                    'max:255'
                ],
                'SPassword' => [
                    $esActualizacion
                        ? 'nullable'
                        : 'required',
                    'string',
                    'min:8',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/[0-9]/',
                    'regex:/[@$!%*?&#]/'
                ],
                'STelefono' => [
                    'nullable',
                    'string',
                    'regex:/^\+[0-9]{8,19}$/'
                ],
                'SIDPerfil' => [
                    $esActualizacion
                        ? 'required'
                        : 'nullable',
                    'string'
                ],
                'SFotoPerfil' => [
                    $esActualizacion
                        ? 'nullable'
                        : 'required',
                    'image',
                    'mimes:jpg,jpeg,png',
                    'max:2048'
                ]
            ],
            $this->MensajesValidacion()
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return null;
    }

    /**
     * Validar el registro público.
     */
    private function ValidarRegistro(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'SNombre' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'SUsuario' => [
                    'required',
                    'email',
                    'max:255'
                ],
                'SPassword' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/[0-9]/',
                    'regex:/[@$!%*?&#]/'
                ],
                'STelefono' => [
                    'nullable',
                    'string',
                    'regex:/^\+[0-9]{8,19}$/'
                ],
                'SFotoPerfil' => [
                    'required',
                    'image',
                    'mimes:jpg,jpeg,png',
                    'max:2048'
                ]
            ],
            $this->MensajesValidacion()
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return null;
    }

    /**
     * Validar la edición del perfil personal.
     */
    private function ValidarMiPerfil(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'SNombre' => [
                    'required',
                    'string',
                    'max:255'
                ],
                'STelefono' => [
                    'nullable',
                    'string',
                    'regex:/^\+[0-9]{8,19}$/'
                ],
                'SFotoPerfil' => [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png',
                    'max:2048'
                ]
            ],
            $this->MensajesValidacion()
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return null;
    }

    /**
     * Mensajes compartidos de validación.
     */
    private function MensajesValidacion(): array
    {
        return [
            'SNombre.required' =>
                'El nombre es obligatorio.',
            'SNombre.string' =>
                'El nombre debe ser texto.',
            'SNombre.max' =>
                'El nombre no puede superar los 255 caracteres.',

            'SUsuario.required' =>
                'El correo es obligatorio.',
            'SUsuario.email' =>
                'Debe ingresar un correo electrónico válido.',
            'SUsuario.max' =>
                'El correo no puede superar los 255 caracteres.',

            'SPassword.required' =>
                'La contraseña es obligatoria.',
            'SPassword.string' =>
                'La contraseña debe ser texto.',
            'SPassword.min' =>
                'La contraseña debe tener mínimo 8 caracteres.',
            'SPassword.confirmed' =>
                'Las contraseñas no coinciden.',
            'SPassword.regex' =>
                'La contraseña debe contener una mayúscula, una minúscula, un número y un carácter especial.',

            'STelefono.string' =>
                'El teléfono debe ser texto.',
            'STelefono.regex' =>
                'El teléfono debe incluir el código del país. Ejemplo: +527712405415.',

            'SIDPerfil.required' =>
                'El perfil es obligatorio.',
            'SIDPerfil.string' =>
                'El identificador del perfil debe ser texto.',

            'SFotoPerfil.required' =>
                'La fotografía de perfil es obligatoria.',
            'SFotoPerfil.image' =>
                'El archivo seleccionado debe ser una imagen.',
            'SFotoPerfil.mimes' =>
                'La fotografía debe estar en formato JPG, JPEG o PNG.',
            'SFotoPerfil.max' =>
                'La fotografía no debe superar los 2 MB.'
        ];
    }

    /**
     * Obtener todos los usuarios.
     */
    public function GetUsuarios()
    {
        $permiso = $this->TienePermiso('USER_VIEW');

        if ($permiso !== true) {
            return $permiso;
        }

        $usuarios = Usuarios::orderBy(
            'created_at',
            'desc'
        )->get();

        return UsuarioResource::collection($usuarios);
    }

    /**
     * Obtener un usuario por ID.
     */
    public function GetUsuario(string $id)
    {
        $permiso = $this->TienePermiso('USER_VIEW');

        if ($permiso !== true) {
            return $permiso;
        }

        $usuario = Usuarios::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        return new UsuarioResource($usuario);
    }

    /**
     * Crear usuario desde el módulo administrativo.
     */
    public function InsertUsuario(Request $request)
    {
        $permiso = $this->TienePermiso('USER_ADDUPD');

        if ($permiso !== true) {
            return $permiso;
        }

        $error = $this->ValidarUsuario(
            $request,
            false
        );

        if ($error) {
            return $error;
        }

        $correo = strtolower(
            trim($request->SUsuario)
        );

        if (Usuarios::where('SUsuario', $correo)->exists()) {
            return response()->json([
                'message' =>
                    'Ya existe un usuario con ese correo.'
            ], Response::HTTP_CONFLICT);
        }

        if ($request->filled('SIDPerfil')) {
            $perfil = Perfiles::find(
                $request->SIDPerfil
            );
        } else {
            $perfil = Perfiles::where(
                'SCodigo',
                'PER_LECTOR_PROD'
            )->first();
        }

        if (!$perfil) {
            return response()->json([
                'message' =>
                    'El perfil seleccionado o predeterminado no existe.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $usuario = $this->CrearUsuario(
            $request,
            $perfil,
            $correo
        );

        return response()->json([
            'message' =>
                'Usuario creado correctamente.',
            'data' => new UsuarioResource($usuario)
        ], Response::HTTP_CREATED);
    }

    /**
     * Registro público desde el login.
     */
    public function RegistrarUsuario(Request $request)
    {
        $error = $this->ValidarRegistro($request);

        if ($error) {
            return $error;
        }

        $correo = strtolower(
            trim($request->SUsuario)
        );

        if (Usuarios::where('SUsuario', $correo)->exists()) {
            return response()->json([
                'message' =>
                    'Ya existe un usuario con ese correo.'
            ], Response::HTTP_CONFLICT);
        }

        /*
         * En el registro público nunca se utiliza
         * un SIDPerfil recibido desde el frontend.
         */
        $perfil = Perfiles::where(
            'SCodigo',
            'PER_LECTOR_PROD'
        )->first();

        if (!$perfil) {
            return response()->json([
                'message' =>
                    'No se encontró el perfil predeterminado PER_LECTOR_PROD.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $usuario = $this->CrearUsuario(
            $request,
            $perfil,
            $correo
        );

        return response()->json([
            'message' =>
                'Usuario registrado correctamente. Ya puedes iniciar sesión.',
            'data' => new UsuarioResource($usuario)
        ], Response::HTTP_CREATED);
    }

    /**
     * Método compartido para guardar un usuario nuevo.
     */
    private function CrearUsuario(
        Request $request,
        Perfiles $perfil,
        string $correo
    ): Usuarios {
        $usuario = new Usuarios();

        $usuario->SCodigo =
            $this->GenerarCodigoUsuario();

        $usuario->SNombre =
            trim($request->SNombre);

        $usuario->SUsuario = $correo;

        $usuario->SPassword = Hash::make(
            $request->SPassword
        );

        $usuario->BPasswordTemporal = false;
        $usuario->TFechaPasswordTemporal = null;

        $usuario->STelefono =
            $request->filled('STelefono')
                ? $request->STelefono
                : null;

        $usuario->SIDPerfil =
            (string) $perfil->_id;

        $archivo = $request->file(
            'SFotoPerfil'
        );

        $nombreFoto =
            $usuario->SCodigo .
            '_' .
            time() .
            '.' .
            $archivo->extension();

        $ruta = Storage::disk('public')->putFileAs(
            'Usuarios',
            $archivo,
            $nombreFoto
        );

        $usuario->SFotoPerfil = $ruta;
        $usuario->save();

        return $usuario;
    }

    /**
     * Actualización administrativa.
     */
    public function UpdateUsuario(
        Request $request,
        string $id
    ) {
        $permiso = $this->TienePermiso('USER_ADDUPD');

        if ($permiso !== true) {
            return $permiso;
        }

        $error = $this->ValidarUsuario(
            $request,
            true
        );

        if ($error) {
            return $error;
        }

        $usuario = Usuarios::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        $correo = strtolower(
            trim($request->SUsuario)
        );

        $existeCorreo = Usuarios::where(
            'SUsuario',
            $correo
        )
            ->where('_id', '!=', $id)
            ->exists();

        if ($existeCorreo) {
            return response()->json([
                'message' =>
                    'Ya existe un usuario con ese correo.'
            ], Response::HTTP_CONFLICT);
        }

        $perfil = Perfiles::find(
            $request->SIDPerfil
        );

        if (!$perfil) {
            return response()->json([
                'message' =>
                    'El perfil seleccionado no existe.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $usuario->SNombre =
            trim($request->SNombre);

        $usuario->SUsuario = $correo;

        $usuario->STelefono =
            $request->filled('STelefono')
                ? $request->STelefono
                : null;

        $usuario->SIDPerfil =
            (string) $perfil->_id;

        if ($request->filled('SPassword')) {
            $usuario->SPassword = Hash::make(
                $request->SPassword
            );
        }

        if ($request->hasFile('SFotoPerfil')) {
            $this->ActualizarFoto(
                $usuario,
                $request
            );
        }

        $usuario->save();
        $usuario->refresh();

        return response()->json([
            'message' =>
                'Usuario actualizado correctamente.',
            'data' => new UsuarioResource($usuario)
        ], Response::HTTP_OK);
    }

    /**
     * Obtener el perfil del usuario autenticado.
     */
    public function MiPerfil()
    {
        $usuarioAutenticado = auth('api')->user();

        if (!$usuarioAutenticado) {
            return response()->json([
                'message' => 'Usuario no autenticado.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        /*
        * Consultar explícitamente el modelo Usuarios
        * para que PHP e Intelephense reconozcan sus métodos.
        */
        $usuario = Usuarios::find(
            $usuarioAutenticado->getAuthIdentifier()
        );

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        return new UsuarioResource($usuario);
    }


    /**
     * Actualizar el perfil del usuario autenticado.
     *
     * Solo permite modificar:
     * - Nombre
     * - Teléfono
     * - Fotografía
     */
    public function ActualizarMiPerfil(Request $request)
    {
        $usuarioAutenticado = auth('api')->user();

        if (!$usuarioAutenticado) {
            return response()->json([
                'message' => 'Usuario no autenticado.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        /*
        * Convertimos el usuario autenticado en una
        * instancia explícita del modelo Usuarios.
        */
        $usuario = Usuarios::find(
            $usuarioAutenticado->getAuthIdentifier()
        );

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        $error = $this->ValidarMiPerfil($request);

        if ($error) {
            return $error;
        }

        $usuario->SNombre = trim(
            $request->SNombre
        );

        $usuario->STelefono =
            $request->filled('STelefono')
                ? $request->STelefono
                : null;

        if ($request->hasFile('SFotoPerfil')) {
            $this->ActualizarFoto(
                $usuario,
                $request
            );
        }

        $usuario->save();

        /*
        * No necesitamos refresh().
        * Después de save(), el objeto ya contiene
        * los datos actualizados.
        */
        return response()->json([
            'message' =>
                'Perfil actualizado correctamente.',
            'data' => new UsuarioResource($usuario)
        ], Response::HTTP_OK);
    }

    /**
     * Reemplazar la fotografía de un usuario.
     */
    private function ActualizarFoto(
        Usuarios $usuario,
        Request $request
    ): void {
        if (
            $usuario->SFotoPerfil &&
            Storage::disk('public')->exists(
                $usuario->SFotoPerfil
            )
        ) {
            Storage::disk('public')->delete(
                $usuario->SFotoPerfil
            );
        }

        $archivo = $request->file(
            'SFotoPerfil'
        );

        $nombreFoto =
            $usuario->SCodigo .
            '_' .
            time() .
            '.' .
            $archivo->extension();

        $ruta = Storage::disk('public')->putFileAs(
            'Usuarios',
            $archivo,
            $nombreFoto
        );

        $usuario->SFotoPerfil = $ruta;
    }

    /**
     * Eliminar usuario.
     */
    public function DeleteUsuario(string $id)
    {
        $permiso = $this->TienePermiso('USER_DELETE');

        if ($permiso !== true) {
            return $permiso;
        }

        $usuario = Usuarios::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        if (
            $usuario->SFotoPerfil &&
            Storage::disk('public')->exists(
                $usuario->SFotoPerfil
            )
        ) {
            Storage::disk('public')->delete(
                $usuario->SFotoPerfil
            );
        }

        $usuario->delete();

        return response()->json([
            'message' =>
                'Usuario eliminado correctamente.'
        ], Response::HTTP_OK);
    }

    /**
     * Generar código automático.
     */
    private function GenerarCodigoUsuario(): string
    {
        $ultimoUsuario = Usuarios::orderBy(
            'SCodigo',
            'desc'
        )->first();

        if (!$ultimoUsuario) {
            return 'USR00001';
        }

        $numero = (int) substr(
            $ultimoUsuario->SCodigo,
            3
        );

        return 'USR' . str_pad(
            $numero + 1,
            5,
            '0',
            STR_PAD_LEFT
        );
    }
}