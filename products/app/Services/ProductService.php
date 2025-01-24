<?php

namespace App\Services;

use App\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Facades\Http;

class ProductService
{
    protected $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function createProduct(array $data)
    {
        $product = $this->productRepository->create($data);
        $this->sendToKafka('create', $product);
        return $product;
    }

    public function updateProduct(int $id, array $data)
    {
        $product = $this->productRepository->update($id, $data);
        $this->sendToKafka('update', $product);
        return $product;
    }

    public function deleteProduct(int $id)
    {
        $product = $this->productRepository->find($id);
        $this->sendToKafka('delete', $product);
        return $this->productRepository->delete($id);
    }

    public function listProducts()
    {
        return $this->productRepository->all();
    }

    private function sendToKafka(string $action, $product)
    {
        Http::withBasicAuth(env('KAFKA_USERNAME'), env('KAFKA_PASSWORD'))
            ->withHeaders([
                'Content-Type' => 'application/vnd.kafka.json.v2+json',
            ])
            ->post(env('KAFKA_REST_PROXY_URL') . '/topics/' . env('KAFKA_TOPIC'), [
                'records' => [
                    [
                        'key' => $action,
                        'value' => $product
                    ]
                ]
            ]);
    }
}