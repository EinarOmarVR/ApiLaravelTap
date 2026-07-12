<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    /**
     * Transformar el recurso en un array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->_id,

            'SCodigo' => $this->SCodigo,
            'SNombre' => $this->SNombre,
            'SUsuario' => $this->SUsuario,
            'STelefono' => $this->STelefono,
            'SFotoPerfil' => $this->SFotoPerfil
                ? url('storage/' . $this->SFotoPerfil)
                : null,
            'SIDPerfil' => $this->SIDPerfil,
            'TFechaCap' => $this->created_at
                ? $this->created_at->format('d/m/Y H:i')
                : null,
            'TFechaMod' => $this->updated_at
                ? $this->updated_at->format('d/m/Y H:i')
                : null,
        ];
    }
}