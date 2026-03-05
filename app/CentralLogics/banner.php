<?php

namespace App\CentralLogics;

use App\Models\Banner;
use App\Models\Item;
use App\Models\Store;
use App\CentralLogics\Helpers;
use App\Models\Category;

class BannerLogic
{
    public static function get_banners($zone_id, $featured = false, $platform = null)
    {
        $banners = Banner::active()
            ->when($featured, function ($query) {
                $query->featured(); 
            })
            ->when($platform, function ($query) use ($platform) {
                $query->platform($platform);
            });
        if (config('module.current_module_data')) {
            $banners = $banners->whereHas('zone.modules', function ($query) {
                $query->where('modules.id', config('module.current_module_data')['id']);
            })
                ->module(config('module.current_module_data')['id'])
                ->when(!config('module.current_module_data')['all_zone_service'], function ($query) use ($zone_id) {
                    $query->whereIn('zone_id', json_decode($zone_id, true));
                });
        }

        $banners = $banners->whereIn('zone_id', json_decode($zone_id, true))->whereHas('module', function ($query) {
            $query->active();
        })->where('created_by', 'admin')
            ->get();

        $data = [];
        foreach ($banners as $banner) {
            if ($banner->type == 'store_wise') {
                $store = Store::active()
                    ->when(config('module.current_module_data'), function ($query) {
                        $query->whereHas('zone.modules', function ($query) {
                            $query->where('modules.id', config('module.current_module_data')['id']);
                        });
                    })
                    ->find($banner->data);
                $data[] = [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => $banner->type,
                    'image' => $banner->image,
                    'link' => null,
                    'store' => $store ? Helpers::store_data_formatting($store, false) : null,
                    'item' => null
                ];
            }
            if ($banner->type == 'item_wise') {
                $item = Item::active()
                    ->when(config('module.current_module_data'), function ($query) use ($zone_id) {
                        $query->whereHas('module.zones', function ($query) use ($zone_id) {
                            $query->whereIn('zones.id', json_decode($zone_id, true));
                        });
                    })
                    ->find($banner->data);
                $data[] = [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => $banner->type,
                    'image' => $banner->image,
                    'link' => null,
                    'store' => null,
                    'item' => $item ? Helpers::product_data_formatting($item, false, false, app()->getLocale()) : null,
                ];
            }
            if ($banner->type == 'default') {
                $data[] = [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => $banner->type,
                    'image' => $banner->image,
                    'link' => $banner->default_link,
                    'store' => null,
                    'item' => null,
                ];
            }
            if ($banner->type == null) {
                $data[] = [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => $banner->type,
                    'image' => $banner->image,
                    'link' => null,
                    'store' => null,
                    'item' => null,
                ];
            }
        }
        return $data;
    }
    public static function get_all_module_banners($zone_id,  $featured = false, $type = null, $data = null, $platform = null)
    {
        $banners = Banner::active()
            ->when($featured, function ($query) {
                $query->featured();
            })
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($data, function ($query) use ($data) {
                $query->where('data', $data);
            })
            ->when($platform, function ($query) use ($platform) {
                $query->platform($platform);
            });

        $banners = $banners->whereIn('zone_id', json_decode($zone_id, true))->whereHas('module', function ($query) {
            $query->active();
        })->where('created_by', 'admin')
            ->get();

        $data = [];
        foreach ($banners as $banner) {
            if ($banner->type == 'store_wise') {
                $store = Store::active()
                    ->find($banner->data);
                $data[] = [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => $banner->type,
                    'image' => $banner->image,
                    'link' => null,
                    'store' => $store ? Helpers::store_data_formatting($store, false) : null,
                    'item' => null
                ];
            }
            if ($banner->type == 'category_wise') {
                $item = Category::active()
                    ->find($banner->data);
                $data[] = [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => $banner->type,
                    'image' => $banner->image,
                    'link' => null,
                    'store' => null,
                    'item' => null,
                ];
            }
            if ($banner->type == 'item_wise') {
                $item = Item::withoutGlobalScopes()->active()
                    ->find($banner->data);
                $data[] = [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => $banner->type,
                    'image' => $banner->image,
                    'link' => null,
                    'store' => null,
                    'item' => $item ? Helpers::product_data_formatting($item, false, false, app()->getLocale()) : null,
                ];
            }
            if ($banner->type == 'default') {
                $data[] = [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => $banner->type,
                    'image' => $banner->image,
                    'link' => $banner->default_link,
                    'store' => null,
                    'item' => null,
                ];
            }
            if ($banner->type == null) {
                $data[] = [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'type' => $banner->type,
                    'image' => $banner->image,
                    'link' => null,
                    'store' => null,
                    'item' => null,
                ];
            }
        }
        return $data;
    }
}
