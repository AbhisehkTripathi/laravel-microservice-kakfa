<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProductService;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\BulkImportRequest;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function addProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = $this->productService->createProduct($validator->validated());

        return response()->json([
            'message' => 'Product added successfully',
            'product' => $product,
        ], 201);
    }

    public function updateProduct(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'string',
            'price' => 'numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = $this->productService->updateProduct($id, $validator->validated());

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product,
        ]);
    }

    public function deleteProduct($id)
    {
        $this->productService->deleteProduct($id);
        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    public function listProducts()
    {
        $products = $this->productService->listProducts();
        return response()->json([
            'products' => $products,
        ], 200);
    }

    // app/Http/Controllers/Api/ProductController.php
    public function bulkImport(BulkImportRequest $request)
    {
        $file = $request->file('csv_file');

        $result = $this->productService->bulkImportProducts($file);

        if ($result['errors']) {
            return response()->json([
                'message' => 'Partial success with some errors',
                'success_count' => $result['success_count'],
                'errors' => $result['errors']
            ], 207);
        }

        return response()->json([
            'message' => 'Bulk import completed successfully',
            'imported_count' => $result['success_count']
        ], 201);
    }
}
