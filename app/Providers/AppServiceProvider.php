<?php

namespace App\Providers;

use App\Output\RedisStreamWrapper;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! in_array('redis_stream', stream_get_wrappers())) {
            stream_wrapper_register('redis', RedisStreamWrapper::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
