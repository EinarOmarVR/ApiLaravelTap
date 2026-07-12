<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerfilResource extends JsonResource
{
    /**
     * Transformar el recurso en un array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->_id,

            'SCodigo' => $this->SCodigo,

            'SPerfil' => $this->SPerfil,

            'SDescripcion' => $this->SDescripcion,

            'APermisos' => $this->APermisos,

            'TFechaCap' => $this->created_at
                ? $this->created_at->format('d/m/Y H:i')
                : null,
            'TFechaMod' => $this->updated_at
                ? $this->updated_at->format('d/m/Y H:i')
                : null,
        ];
    }
}