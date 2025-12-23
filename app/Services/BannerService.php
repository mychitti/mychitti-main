<?php

namespace App\Services;

use App\Traits\FileManagerTrait;
use DateInterval;
use DateTime;
use Illuminate\Support\Facades\Config;

class BannerService
{
    use FileManagerTrait;

    public function getAddData(Object $request): array
    {
        if($request->user_id){ // if paid banner
            $data_datetime = new DateTime(date('Y-m-d H:i:s'));

            if ($request->validity_type == 'Months') {
                $interval = new DateInterval("P{$request->validity_count}M");
            } elseif ($request->validity_type == 'Days') {
                $interval = new DateInterval("P{$request->validity_count}D");
            } elseif ($request->validity_type == 'Years') {
                $interval = new DateInterval("P{$request->validity_count}Y");
            }

            $expiry_date = clone $data_datetime;
            $expiry_date->add($interval);
            $expiry_date_formatted = $expiry_date->format('Y-m-d H:i:s');
        }
        return [
            'title' => $request->title[array_search('default', $request->lang)],
            'type' => $request->banner_type,
            'zone_id' => $request->zone_id,
            'image' => $this->upload('banner/', 'png', $request->file('image')),
            'data' => $request->banner_type == 'category_wise' ? $request->category_id :  ($request->banner_type == 'module_wise' ? $request->module_id : ( ($request->banner_type == 'store_wise')?$request->store_id:(($request->banner_type == 'item_wise')?$request->item_id:''))) ,
            'module_id' => Config::get('module.current_module_id'),
            'default_link' => $request->default_link,
            'user_id' => $request->user_id ?? null,
            'paid' =>  $request->user_id ? 1 : 0,
            'price' => $request->user_id ?   $request->price : null ,
            'validity_count' => $request->user_id ? $request->validity_count: null,
            'validity_type' => $request->user_id ? $request->validity_type: null,
            'expiry_date' => $request->user_id ? $expiry_date_formatted : null
        ];
    }
    public function getUpdateData(Object $request, object $banner): array
    {
        return [
            'title' => $request->title[array_search('default', $request->lang)],
            'type' => $request->banner_type,
            'zone_id' => $request->zone_id,
            'image' => $request->has('image') ? $this->updateAndUpload('banner/', $banner->image, 'png', $request->file('image')) : $banner->image,
            'data' => ($request->banner_type == 'store_wise')?$request->store_id:(($request->banner_type == 'item_wise')?$request->item_id:''),
            'module_id' => Config::get('module.current_module_id'),
            'default_link' => $request->default_link
        ];
    }

}
