<?php

namespace App\Repositories;

use App\Models\Product;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function create(array $data)
    {
        return Product::create($data);
    }

    public function update(int $id, array $data)
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product;
    }

    public function delete(int $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return true;
    }

    public function all()
    {
        return Product::all();
    }

    public function find(int $id)
    {
        return Product::findOrFail($id);
    }
}