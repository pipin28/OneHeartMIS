<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
        View::composer('*', function ($view) {
            $defaultName = 'OneHeart Life Plan';

            try {
                $branding = DB::table('branding_settings')->first();
            } catch (Throwable) {
                $branding = null;
            }

            $name = trim((string) ($branding->company_name ?? $defaultName));
            if ($name === '') {
                $name = $defaultName;
            }

            $logoPath = (string) ($branding->logo_path ?? '');
            if ($logoPath !== '') {
                $logoUrl = asset($logoPath);
            } else {
                $initial = strtoupper(substr($name, 0, 1) ?: 'A');
                $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">'
                    . '<rect width="96" height="96" rx="20" fill="#19170f"/>'
                    . '<text x="48" y="61" text-anchor="middle" font-family="Arial, sans-serif" font-size="42" font-weight="700" fill="#d9c27a">'
                    . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8')
                    . '</text></svg>';
                $logoUrl = 'data:image/svg+xml,' . rawurlencode($svg);
            }

            $view->with('appBrandName', $name);
            $view->with('appBrandLogoUrl', $logoUrl);
        });
    }
}
