<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Native\Mobile\Providers\CameraServiceProvider;
use Native\Mobile\UI\NativeUIServiceProvider;
use Vipertecpro\ImageCropper\ImageCropperServiceProvider;
use Vipertecpro\WysiwygEditor\WysiwygEditorServiceProvider;

class NativeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * The NativePHP plugins to enable.
     *
     * Only plugins listed here will be compiled into your native builds.
     * This is a security measure to prevent transitive dependencies from
     * automatically registering plugins without your explicit consent.
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    public function plugins(): array
    {
        return [
            WysiwygEditorServiceProvider::class,
            NativeUIServiceProvider::class,
            CameraServiceProvider::class,
            ImageCropperServiceProvider::class,

        ];
    }
}
