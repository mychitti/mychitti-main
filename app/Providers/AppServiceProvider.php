<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;

use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use App\Traits\AddonHelper;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use App\CentralLogics\Helpers;
use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use App\Models\Category;
use App\Models\Item;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    use AddonHelper;
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
         Paginator::useBootstrap();
        // View::composer('*', function ($view) {
        //     $zone_id = Session::get('zone_ids', json_encode([])); // stored by _setLocation()

        //     // Get the base categories with item counts
        //     $service_categories2 = Category::select('categories.*', DB::raw('(
        //     SELECT COUNT(*) FROM items 
        //     WHERE items.category_id = categories.id AND items.status = 1
        // ) as items_count'))
        //         ->where(['categories.status' => 1, 'categories.position' => 0])
        //         ->where('categories.module_id', 6)
        //         ->inRandomOrder()
        //         ->get();

        //     // Attach related items for each category
        //     foreach ($service_categories2 as $key => $value) {
        //         $service_categories2[$key]['cat_items'] = Item::withoutGlobalScopes()
        //             ->join('stores', function ($join) use ($zone_id) {
        //                 $join->whereRaw('FIND_IN_SET(stores.id, items.store_ids) > 0');
        //                 $join->whereIn('stores.zone_id', json_decode($zone_id, true));
        //             })
        //             ->join('zones', 'zones.id', '=', 'stores.zone_id')
        //             ->join('categories', 'categories.id', '=', 'items.category_id')
        //             ->where('items.status', 1)
        //             ->where('items.category_id', $value->id)
        //             ->select(
        //                 'items.*',
        //                 'categories.slug as cat_slug',
        //                 'zones.name as zone_name',
        //                 DB::raw('COUNT(stores.id) as store_count')
        //             )
        //             ->groupBy('items.id', 'zones.name', 'categories.slug')
        //             ->inRandomOrder()
        //             ->take(4)
        //             ->get();
        //     }

        //     $view->with('service_categories2', $service_categories2);
        // });


        $host = request()->getHost();

        if (Str::contains($host, 'vendor-staff')) {
            config(['session.cookie' => 'vendor_employee_session']);
        } elseif (Str::contains($host, 'vendor')) {
            config(['session.cookie' => 'vendor_session']);
        }
        if (request()->getHost() === 'vendor-staff.mcvendorhub.com') {

            // Override the global route() helper function
            $this->app->singleton('url', function ($app) {
                $routes = $app['router']->getRoutes();
                $request = $app['request'];

                $url = new \Illuminate\Routing\UrlGenerator($routes, $request);
                $url->forceRootUrl('https://vendor-staff.mcvendorhub.com');

                return $url;
            });
        }

        Carbon::serializeUsing(function ($date) {
            return $date->format('Y-m-d H:i:s');
        });

        try {
            Config::set('addon_admin_routes', $this->get_addon_admin_routes());
            Config::set('get_payment_publish_status', $this->get_payment_publish_status());
            Paginator::useBootstrap();
            foreach (Helpers::get_view_keys() as $key => $value) {
                view()->share($key, $value);
            }
        } catch (\Exception $e) {
        }
    }
}
