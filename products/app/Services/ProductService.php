<?php

namespace App\Services;

use App\Repositories\ProductRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;

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

    public function bulkImportProducts($file)
{
    return DB::transaction(function () use ($file) {
        $csv = Reader::createFromPath($file->getRealPath(), 'r');
        $csv->setHeaderOffset(0);
        
        $successCount = 0;
        $errors = [];
        
        foreach ($csv as $index => $row) {
            try {
                $validated = $this->validateCsvRow($row);
                $product = $this->productRepository->create($validated);
                $this->sendToKafka('product_created', $product);
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $index + 2, // +1 for header, +1 for 0-index
                    'message' => $e->getMessage()
                ];
            }
        }

        $totalAttempted = count(file($file->getRealPath())) - 1; // Subtract header


        // Send bulk completion event
        $this->sendBulkCompletionEvent(
                successCount: $successCount,
            totalAttempted: $totalAttempted,
            file: $file
        );

        return [
            'success_count' => $successCount,
            'errors' => $errors
        ];
    });
}

private function validateCsvRow(array $row)
{
    $validator = Validator::make($row, [
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'price' => 'required|numeric|min:0'
    ]);

    if ($validator->fails()) {
        throw ValidationException::withMessages($validator->errors()->toArray());
    }

    return $validator->validated();
}

private function sendBulkCompletionEvent(int $successCount, int $totalAttempted, $file)
{
    $event = [
        'event_id' => Str::uuid()->toString(),
        'event_type' => 'bulk_import_completed',
        'timestamp' => now()->toISOString(),
        'data' => [
            'job_id' => 'bulk-' . now()->format('Ymd-His'),
            'imported_count' => $successCount,
            'total_attempted' => $totalAttempted,
            'user_id' => auth()->id(),
            'product_ids' => $this->getLatestProductIds($successCount),
            'metadata' => [
                'filename' => $file->getClientOriginalName(),
                'file_size' => $this->formatBytes($file->getSize()),
                'mime_type' => $file->getMimeType()
            ]
        ]
    ];

    Http::withBasicAuth(env('KAFKA_USERNAME'), env('KAFKA_PASSWORD'))
        ->withHeaders([
            'Content-Type' => 'application/vnd.kafka.json.v2+json',
        ])
        ->post(env('KAFKA_REST_PROXY_URL') . '/topics/' . env('KAFKA_TOPIC'), [
            'records' => [
                [
                    'key' => 'bulk_import',
                    'value' => $event
                ]
            ]
        ]);
}

private function getLatestProductIds(int $count)
{
    return $this->productRepository
        ->getLatest($count)
        ->pluck('id')
        ->toArray();
}

private function formatBytes($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
}
}