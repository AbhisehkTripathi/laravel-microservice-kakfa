<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    // Add a new product
    public function addProduct_old(Request $request)
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

        $validated = $validator->validated();

        // Save to database
        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
        ]);

        // Publish to Kafka
        $response = Http::withBasicAuth(env('KAFKA_USERNAME'), env('KAFKA_PASSWORD'))
            ->withHeaders([
                'Content-Type' => 'application/vnd.kafka.json.v2+json',
            ])
            ->post(env('KAFKA_REST_PROXY_URL') . '/topics/' . env('KAFKA_TOPIC'), [
                'records' => [
                    [
                        'key' => 'product',
                        'value' => $validated,
                    ],
                ],
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Product saved to database, but failed to produce message to Kafka.',
                'error' => $response->json(),
            ], 500);
        }

        return response()->json([
            'message' => 'Product added successfully',
            'product' => $product,
        ], 201);
    }




    public function listProducts_old()
    {
        $products = Product::with('owner')->get();

        return response()->json([
            'products' => $products,
        ], 200);
    }

    public function listProducts()
    {
        $consumerName = env('KAFKA_CONSUMER_INSTANCE', 'consumer-instance-' . uniqid());

        // Register the consumer
        $registerResponse = Http::withBasicAuth(env('KAFKA_USERNAME'), env('KAFKA_PASSWORD'))
            ->withHeaders([
                'Content-Type' => 'application/vnd.kafka.v2+json',
            ])
            ->post(env('KAFKA_REST_PROXY_URL') . '/consumers/' . env('KAFKA_GROUP'), [
                'name' => $consumerName,
                'format' => 'json', // Ensure JSON format is set
                'auto.offset.reset' => 'earliest',
            ]);

        if ($registerResponse->failed()) {
            return response()->json([
                'message' => 'Failed to register consumer.',
                'error' => $registerResponse->json(),
            ], 500);
        }

        // Fetch messages
        $fetchResponse = Http::withBasicAuth(env('KAFKA_USERNAME'), env('KAFKA_PASSWORD'))
            ->get(env('KAFKA_REST_PROXY_URL') . '/consumers/' . env('KAFKA_GROUP') . '/instances/' . $consumerName . '/records');

        if ($fetchResponse->failed()) {
            // Clean up the consumer instance in case of failure
            Http::withBasicAuth(env('KAFKA_USERNAME'), env('KAFKA_PASSWORD'))
                ->delete(env('KAFKA_REST_PROXY_URL') . '/consumers/' . env('KAFKA_GROUP') . '/instances/' . $consumerName);

            return response()->json([
                'message' => 'Failed to fetch records.',
                'error' => $fetchResponse->json(),
            ], 500);
        }

        // Clean up the consumer instance
        Http::withBasicAuth(env('KAFKA_USERNAME'), env('KAFKA_PASSWORD'))
            ->delete(env('KAFKA_REST_PROXY_URL') . '/consumers/' . env('KAFKA_GROUP') . '/instances/' . $consumerName);

        return response()->json([
            'products' => $fetchResponse->json(),
        ], 200);
    }
}
