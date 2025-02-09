<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Lunfel\RedisStreamWrapper\RedisStreamWrapper;

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
