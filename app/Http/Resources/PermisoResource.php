<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermisoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->_id,
            'SCodigo' => $this->SCodigo,
            'SPermiso' => $this->SPermiso,
            'SModulo' => $this->SModulo,
            'SDescripcion' => $this->SDescripcion,
            'TFechaCap' => $this->created_at,
            'TFechaMod' => $this->updated_at,
        ];
    }
}