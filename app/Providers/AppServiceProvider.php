<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Native\Mobile\UI\Theme;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadTheme();

        //
    }

    /**
     * The app's palette: blue on zinc.
     *
     * Loaded HERE rather than in NativeServiceProvider, which is resolved for
     * its plugin list rather than registered as a provider — its boot() never
     * runs, so a theme set there silently does nothing.
     *
     * The editor is given NO `theme` option anywhere in this demo. It reads
     * these tokens itself, per colour scheme, so opening it looks like opening
     * another screen of the same app rather than a plugin bolted on.
     */
    protected function loadTheme(): void
    {
        Theme::load([
            'light' => [
                'background' => '#FAFAFA',          // zinc-50
                'surface' => '#FFFFFF',
                'on-background' => '#18181B',       // zinc-900
                'on-surface' => '#27272A',          // zinc-800
                'on-surface-variant' => '#52525B',  // zinc-600
                'outline' => '#E4E4E7',             // zinc-200
                'primary' => '#2563EB',             // blue-600
                'on-primary' => '#FFFFFF',
                'secondary' => '#3B82F6',           // blue-500
            ],
            'dark' => [
                'background' => '#09090B',          // zinc-950
                'surface' => '#18181B',             // zinc-900
                'on-background' => '#FAFAFA',       // zinc-50
                'on-surface' => '#E4E4E7',          // zinc-200
                'on-surface-variant' => '#A1A1AA',  // zinc-400
                'outline' => '#3F3F46',             // zinc-700
                // blue-400 rather than blue-600: 600 on zinc-950 is too dark
                // to read as an accent.
                'primary' => '#60A5FA',
                'on-primary' => '#09090B',
                'secondary' => '#3B82F6',           // blue-500
            ],
        ]);
    }
}
