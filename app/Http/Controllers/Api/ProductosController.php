<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use App\Models\Productos;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
class ProductosController extends BaseController
{
   private function ValidarProducto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'SProducto' => 'required|string|max:255',
            'SMarca' => 'required|string|max:255',
            'DPrecio' => 'required|numeric|min:1|max:999'
        ], [
            'SProducto.required' => 'El nombre del producto es obligatorio.',
            'SProducto.string' => 'El nombre del producto debe ser texto.',

            'SMarca.required' => 'La marca del producto es obligatoria.',
            'SMarca.string' => 'La marca debe ser texto.',

            'DPrecio.required' => 'El precio es obligatorio.',
            'DPrecio.numeric' => 'El precio debe ser un número.',
            'DPrecio.min' => 'El precio no puede ser menor a 0.',
            'DPrecio.max' => 'El precio no puede superar los 3 dígitos.'
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
     * Obtener todos los productos
     */
    public function GetProductos()
    {

        $productos = Productos::orderBy('TFechaCreacion', 'desc')->get();

        return ProductoResource::collection($productos);
    }

    /**
     * Obtener un producto por Id
     */
    public function GetProducto(string $id)
    {
        $producto = Productos::find($id);

        if (!$producto) {
            return response()->json([
                'message' => 'Producto no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        return new ProductoResource($producto);
    }

    /**
     * Crear producto
     */
    public function InsertProduct(Request $request)
    {
        $permiso = $this->TienePermiso('PROD_AddUpd');

        if ($permiso !== true) {
            return $permiso;
        }
                $error = $this->ValidarProducto($request);

        if ($error) {
            return $error;
        }
        // Generar código automático
        $SCodigo = $this->GenerarCodigoProducto();

        $producto = new Productos();

        $producto->SCodigo = $SCodigo;
        $producto->SProducto = $request->SProducto;
        $producto->SMarca = $request->SMarca;
        $producto->DPrecio = $request->DPrecio;

        $producto->save();

        return response()->json([
            'message' => 'Producto creado correctamente.',
            'data' => $producto
        ], Response::HTTP_CREATED);
    }

    /**
     * Actualizar producto
     */
    public function UpdateProduct(Request $request, string $id)
    {
        $permiso = $this->TienePermiso('PROD_AddUpd');

        if ($permiso !== true) {
            return $permiso;
        }
        
        $error = $this->ValidarProducto($request);

        if ($error) {
            return $error;
        }

        $producto = Productos::find($id);

        if (!$producto) {
            return response()->json([
                'message' => 'Producto no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        $producto->SProducto = $request->SProducto;
        $producto->SMarca = $request->SMarca;
        $producto->DPrecio = $request->DPrecio;

        $producto->save();

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'data' => $producto
        ], Response::HTTP_OK);
    }

    /**
     * Eliminar producto
     */
    public function DeleteProduct(string $id)
    {
        $permiso = $this->TienePermiso('PROD_DELETE');

        if ($permiso !== true) {
            return $permiso;
        }
        $producto = Productos::find($id);

        if (!$producto) {
            return response()->json([
                'message' => 'Producto no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        $producto->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente.'
        ], Response::HTTP_OK);
    }

    /**
     * Generar código automático
     */
    private function GenerarCodigoProducto()
    {
        $ultimoProducto = Productos::orderBy('SCodigo', 'desc')->first();

        if (!$ultimoProducto) {
            return 'P00001';
        }

        $INumero = (int) substr($ultimoProducto->SCodigo, 1);

        return 'P' . str_pad($INumero + 1, 5, '0', STR_PAD_LEFT);
    }
}