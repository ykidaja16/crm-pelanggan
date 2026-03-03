<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cabang;

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
        // Nonaktifkan lazy loading di production untuk mendeteksi N+1 lebih awal
        // Model::preventLazyLoading(! app()->isProduction());

        // Cache Cabang list — data cabang jarang berubah, cache 1 jam
        // Digunakan di banyak controller: PelangganController, LaporanController, dll.
        // Gunakan helper: app('cabangs') atau Cache::get('cabangs_all')
        $this->app->singleton('cabangs', function () {
            return Cache::remember('cabangs_all', 3600, function () {
                return Cabang::orderBy('nama')->get();
            });
        });
    }
}
