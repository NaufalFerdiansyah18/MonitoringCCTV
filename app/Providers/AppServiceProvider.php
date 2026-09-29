<?php

namespace App\Providers;

use App\Models\Alarm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();

            $unseenAlarmCount = $user
                ? Alarm::query()
                    ->whereNull('seen_at')
                    ->whereHas('camera.dvr.unit', function ($query) use ($user) {
                        $query->whereIn('kategori', $user->allowedUnitCategories()->all());
                    })
                    ->count()
                : 0;

            $sidebarDvrs = $user
                ? \App\Models\Dvr::query()
                    ->whereHas('unit', function ($query) use ($user) {
                        $query->whereIn('kategori', $user->allowedUnitCategories()->all());
                    })
                    ->with('unit')
                    ->withCount('cameras')
                    ->orderBy('nama')
                    ->get()
                : collect();

            $view->with([
                'unseenAlarmCount' => $unseenAlarmCount,
                'sidebarDvrs' => $sidebarDvrs,
            ]);
        });
    }
}
