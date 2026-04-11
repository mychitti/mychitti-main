<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\CentralLogics\Helpers;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $search  = $request->search;

        $blogs = BlogPost::where('store_id', $storeId)
            ->where('posted_by', 'store')
            ->when($search, fn($q) => $q->where('title', 'like', "%$search%"))
            ->orderByDesc('created_at')
            ->paginate(config('default_pagination', 10));

        return view('vendor-views.blog.index', compact('blogs', 'search'));
    }

    public function create()
    {
        $categories = BlogCategory::where('status', 1)->get();
        return view('vendor-views.blog.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|max:191',
            'image'       => 'required|mimes:jpg,jpeg,png,bmp,tiff|max:30720',
            'description' => 'required',
            'category_id' => 'required|exists:blog_categories,id',
            'type'        => 'required|in:common,mc_vendor',
        ]);

        $blog                    = new BlogPost();
        $blog->title             = $request->title;
        $blog->slug              = Str::slug($request->title) . '-' . Str::random(5);
        $blog->image             = Helpers::upload('blog/', 'png', $request->file('image'));
        $blog->category_id       = $request->category_id;
        $blog->description       = $request->description;
        $blog->short_description = $request->short_description;
        $blog->type              = $request->type;
        $blog->store_id          = Helpers::get_store_id();
        $blog->posted_by         = 'store';
        $blog->status            = 0; // pending admin approval
        $blog->save();

        Toastr::success('Blog post submitted for approval.');
        return redirect()->route('vendor.blog.index');
    }

    public function edit($id)
    {
        $storeId    = Helpers::get_store_id();
        $blog       = BlogPost::where('id', $id)->where('store_id', $storeId)->where('posted_by', 'store')->firstOrFail();
        $categories = BlogCategory::where('status', 1)->get();
        return view('vendor-views.blog.edit', compact('blog', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $storeId = Helpers::get_store_id();
        $blog    = BlogPost::where('id', $id)->where('store_id', $storeId)->where('posted_by', 'store')->firstOrFail();

        $request->validate([
            'title'       => 'required|max:191',
            'description' => 'required',
            'category_id' => 'required|exists:blog_categories,id',
            'type'        => 'required|in:common,mc_vendor',
        ]);

        $blog->title             = $request->title;
        $blog->slug              = Str::slug($request->title) . '-' . Str::random(5);
        $blog->image             = $request->hasFile('image') ? Helpers::update('blog/', $blog->image, 'png', $request->file('image')) : $blog->image;
        $blog->category_id       = $request->category_id;
        $blog->description       = $request->description;
        $blog->short_description = $request->short_description;
        $blog->type              = $request->type;
        $blog->status            = 0; // re-submit for approval on edit
        $blog->save();

        Toastr::success('Blog post updated and re-submitted for approval.');
        return redirect()->route('vendor.blog.index');
    }

    public function destroy($id)
    {
        $storeId = Helpers::get_store_id();
        $blog    = BlogPost::where('id', $id)->where('store_id', $storeId)->where('posted_by', 'store')->firstOrFail();
        $blog->delete();

        Toastr::success('Blog post deleted.');
        return back();
    }

    public function getCategoriesByType(Request $request)
    {
        $type       = $request->type ?? 'common';
        $categories = BlogCategory::where('status', 1)->where('type', $type)->get(['id', 'name']);
        return response()->json($categories->map(fn($c) => ['id' => $c->id, 'text' => $c->name]));
    }

    public function uploadImage(Request $request)
    {
        $request->validate(['upload' => 'required|image|mimes:jpeg,png,jpg,gif|max:30720']);

        if ($request->hasFile('upload')) {
            $imagePath = Helpers::upload('blog_content_images/', 'png', $request->file('upload'));
            return response()->json([
                'uploaded' => true,
                'url'      => asset('/storage/app/public/blog_content_images/' . $imagePath),
            ]);
        }

        return response()->json(['uploaded' => false, 'error' => ['message' => 'No file uploaded.']], 400);
    }
}
