<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    // Add a new product
    public function addProduct(Request $request)
    {
           
        // if ($request->user()->usertype !== 'owner') {
        //     return response()->json(['message' => 'Only owners can add products.'], 403);
        // }

        // Use Validator for manual validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
        ], [
            'name.required' => 'The product name is required.',
            'description.required' => 'The product description is required.',
            'price.required' => 'The product price is required.',
            'price.numeric' => 'The product price must be a number.',
            'price.min' => 'The product price must be at least 0.',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create the product using validated data
        $validated = $validator->validated();

        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            // 'owner_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Product added successfully',
            'product' => $product,
        ], 201);
    }

    public function listProducts()
    {
        $products = Product::with('owner')->get();

        return response()->json([
            'products' => $products,
        ], 200);
    }
}
