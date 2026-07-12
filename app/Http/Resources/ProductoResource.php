<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'SClave'  => $this->_id,
            'SCodigo' => $this->SCodigo,
            'SProducto' => $this->SProducto,
            'SMarca' => $this->SMarca,
            'DPrecio' => $this->DPrecio,
            'TFechaCap' => $this->created_at
                ? $this->created_at->format('d/m/Y H:i')
                : null,
            'TFechaMod' => $this->updated_at
                ? $this->updated_at->format('d/m/Y H:i')
                : null,
        ];
    }
}