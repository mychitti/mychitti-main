<?php

namespace App\Services;

use App\Traits\FileManagerTrait;
use Illuminate\Support\Facades\Config;

class BannerService
{
    use FileManagerTrait;

    public function getAddData(Object $request): array
    {
        return [
            'title' => $request->title[array_search('default', $request->lang)],
            'type' => $request->banner_type,
            'zone_id' => $request->zone_id,
            'image' => $this->upload('banner/', 'png', $request->file('image')),
            'data' => $request->banner_type == 'category_wise' ? $request->category_id : ($request->banner_type == 'module_wise' ? $request->module_id : (($request->banner_type == 'store_wise') ? $request->store_id : (($request->banner_type == 'item_wise') ? $request->item_id : ''))),
            'module_id' => Config::get('module.current_module_id'),
            'default_link' => $request->default_link,
            'platform' => $request->platform ?? 'all',
            'user_id' => $request->user_id ?? null,
            'paid' => $request->user_id ? 1 : 0,
            'price' => $request->price ?: null,
            'expiry_date' => $request->expiry_date ?: null,
            'publish_at' => $request->publish_at ?: null,
            'status' => ($request->publish_at && $request->publish_at > now()) ? 0 : 1,
        ];
    }
    public function getUpdateData(Object $request, object $banner): array
    {
        return [
            'title' => $request->title[array_search('default', $request->lang)],
            'type' => $request->banner_type,
            'zone_id' => $request->zone_id,
            'image' => $request->has('image') ? $this->updateAndUpload('banner/', $banner->image, 'png', $request->file('image')) : $banner->image,
            'data' => ($request->banner_type == 'store_wise') ? $request->store_id : (($request->banner_type == 'item_wise') ? $request->item_id : ''),
            'module_id' => Config::get('module.current_module_id'),
            'default_link' => $request->default_link,
            'platform' => $request->platform ?? $banner->platform,
            'expiry_date' => $request->expiry_date ?: $banner->expiry_date,
            'publish_at' => $request->publish_at ?: null,
            'status' => ($request->publish_at && $request->publish_at > now()) ? 0 : $banner->status,
        ];
    }

}
