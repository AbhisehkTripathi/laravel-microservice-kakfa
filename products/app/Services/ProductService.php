<?php

namespace App\Services;

use App\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductService
{
    protected $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function createProduct(array $data)
    {
        return DB::transaction(function () use ($data) {
            $product = $this->productRepository->create($data);
            $this->sendToKafka('product_created', $product);
            return $product;
        });
    }
\
    public function updateProduct(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $oldProduct = $this->productRepository->find($id);
            $product = $this->productRepository->update($id, $data);
            $this->sendToKafka('product_updated', $product, $oldProduct);
            return $product;
        });
    }

    public function deleteProduct(int $id)
    {
        return DB::transaction(function () use ($id) {
            $product = $this->productRepository->find($id);
            $this->sendToKafka('product_deleted', $product);
            return $this->productRepository->delete($id);
        });
    }

    public function listProducts()
    {
        return $this->productRepository->all();
    }

    private function sendToKafka(string $eventType, $product, $oldProduct = null)
    {
        try {
            $event = $this->formatEvent($eventType, $product, $oldProduct);
            
            $response = Http::withBasicAuth(env('KAFKA_USERNAME'), env('KAFKA_PASSWORD'))
                ->withHeaders([
                    'Content-Type' => 'application/vnd.kafka.json.v2+json',
                ])
                ->post(env('KAFKA_REST_PROXY_URL') . '/topics/' . env('KAFKA_TOPIC'), [
                    'records' => [
                        [
                            'key' => (string) $product->id, // Use product ID as key
                            'value' => $event
                        ]
                    ]
                ]);

            if ($response->failed()) {
                throw new \Exception('Kafka publish failed: ' . $response->body());
            }

        } catch (\Exception $e) {
            \Log::error('Kafka event failed', [
                'error' => $e->getMessage(),
                'event' => $event ?? null
            ]);
            throw $e; // Will trigger transaction rollback
        }
    }

    private function formatEvent(string $eventType, $product, $oldProduct = null): array
    {
        $baseEvent = [
            'event_id' => Str::uuid()->toString(),
            'event_type' => $eventType,
            'timestamp' => now()->toISOString(),
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price // Ensure numeric type
            ]
        ];

        if ($eventType === 'product_updated' && $oldProduct) {
            $baseEvent['changes'] = [
                'price' => [
                    'old' => (float) $oldProduct->price,
                    'new' => (float) $product->price
                ]
            ];
        }

        if ($eventType === 'product_deleted') {
            // For deletes, send minimal data
            $baseEvent['data'] = ['id' => $product->id];
        }

        return $baseEvent;
    }
}