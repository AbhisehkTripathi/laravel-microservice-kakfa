<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KafkaProducerService;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    protected $kafkaProducer;

    public function __construct(KafkaProducerService $kafkaProducer)
    {
        $this->kafkaProducer = $kafkaProducer;
    }

    public function addProduct(Request $request)
    {
        // if ($request->user()->usertype !== 'owner') {
        //     return response()->json(['message' => 'Only owners can add products.'], 403);
        // }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'owner_id' => $request->user()->id,
        ]);

        
        $this->kafkaProducer->send(config('kafka.topic'), [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            // 'owner_id' => $product->owner_id,
        ]);

        return response()->json(['message' => 'Product added successfully', 'product' => $product], 201);
    }
}
