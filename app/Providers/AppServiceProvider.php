<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (Schema::hasTable('settings')) {
            // Get data setting
            $getSetting = Setting::find(1);
            // Logo and app_name
            config(['adminlte.title' => $getSetting->app_name]);
            config(['adminlte.logo' => $getSetting->app_name]);
            config(['adminlte.logo_img' => 'img/' . $getSetting->logo]);

            config(['adminlte.name_favicon' => $getSetting->favicons]);
            config(['adminlte.copyright' => $getSetting->copyright]);

            // Color
            config(['adminlte.classes_brand' => 'bg-' . $getSetting->color]);
            config(['adminlte.classes_sidebar' => 'sidebar-light-' . $getSetting->color . ' elevation-4']); // can use light or dark
            config(['adminlte.classes_topnav' => 'navbar-' . $getSetting->color . ' navbar-dark']);
        }
    }
}
