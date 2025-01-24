<?php

namespace App\Providers;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\EloquentProductRepository;
use Illuminate\Support\ServiceProvider;
use App\Services\ProductService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class,EloquentProductRepository::class);
        
        $this->app->bind(\App\Services\ProductService::class, function($app) {
            return new \App\Services\ProductService(
                $app->make(\App\Repositories\ProductRepositoryInterface::class)
            );
        });
    
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
