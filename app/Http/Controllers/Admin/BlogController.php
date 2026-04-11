<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use App\Models\VendorEmployee;
use App\Models\BlogPost;
use App\CentralLogics\Helpers;
use Illuminate\Support\Str;
use App\Models\BlogCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{


    public function uploadImage(Request $request)
    {
        $request->validate([
            'upload' => 'required|image|mimes:jpeg,png,jpg,gif|max:30720'
        ]);

        if ($request->hasFile('upload')) {
            $imagePath = Helpers::upload('blog_content_images/', 'png', $request->file('upload'));

            return response()->json([
                'uploaded' => true,
                'url' => asset('/storage/app/public/blog_content_images/' . $imagePath)
            ]);
        }

        return response()->json([
            'uploaded' => false,
            'error' => [
                'message' => 'No file was uploaded.'
            ]
        ], 400);
    }
    public function index(Request $request)
    {
        $type  = $request->get('type', 'common');
        $blogs = DB::table('blog_posts')
            ->join('blog_categories', 'blog_categories.id', 'blog_posts.category_id')
            ->select('blog_posts.*', 'blog_categories.name as cat_name')
            ->where('blog_posts.type', $type)
            ->orderByDesc('blog_posts.created_at')
            ->paginate(10);
        return view('admin-views.blog.list', compact('blogs', 'type'));
    }
    public function category(Request $request)
    {
        $type       = $request->get('type', 'common');
        $categories = BlogCategory::where('type', $type)->paginate(10);
        return view('admin-views.blog.category', compact('categories', 'type'));
    }
    public function add_new(Request $request)
    {
        $type       = $request->get('type', 'common');
        $categories = BlogCategory::where('status', 1)->where('type', $type)->get();
        return view('admin-views.blog.add', compact('blogs', 'categories', 'type'));
    }
    public function category_select_type(Request $request)
    {
        $type = $request->type ?? 'common';
        $categories = BlogCategory::where('status', 1)->where('type', $type)->get();
        $formattedCategories = [];
        foreach ($categories as $category) {
            $formattedCategories[] = ['id' => $category->id, 'text' => $category->name];
        }
        return response()->json($formattedCategories);
    }

    public function update(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'image' => 'nullable|mimes:jpg,jpeg,png,bmp,tiff|max:30720',
            'description' => 'required',
            'category' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $blog =  BlogPost::find($request->id);
        $blog->title = $request->post('name');
        $blog->slug = Str::slug($request->name);
        $blog->image = $request->has('image') ? Helpers::update('blog/', $blog->image, 'png', $request->file('image')) : $blog->image;
        $blog->category_id = $request->category;
        $blog->description = $request->post('description');
        $blog->short_description = $request->short_description;
        $blog->type = $request->type;
        $save = $blog->update();


        return response()->json(['msg' => 'Blog Post saved successfully', 'status' => true]);
    }
    public function save(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:191',
            'image' => 'required|mimes:jpg,jpeg,png,bmp,tiff|max:30720',
            'description' => 'required',
            'category' => 'required',
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $blog = new BlogPost;
        $blog->title = $request->post('name');
        $blog->slug = Str::slug($request->name);
        $blog->image = Helpers::upload('blog/', 'png', $request->file('image'));
        $blog->category_id = $request->category;
        $blog->description = $request->description;
        $blog->short_description = $request->short_description;
        $blog->type = $request->type;
        $save = $blog->save();
        if ($save) {
            return response()->json(['msg' => 'Blog Post saved successfully', 'status' => true]);
        } else {
            return response()->json(['msg' => 'Some error occured', 'status' => false]);
        }
    }

    public function category_store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:blog_categories|max:191',
            'image' => 'required|mimes:jpg,jpeg,png,bmp,tiff|max:30720',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $category = new BlogCategory;
        $category->name = $request->name;
        $category->type = $request->type ?? 'common';
        $category->slug = Str::slug($request->name);
        $category->image = Helpers::upload('blog/category/', 'png', $request->file('image'));;
        $category->created_at = now();
        $saved =   $category->save();
        if ($saved) {
            Toastr::success('Category saved successfully');
        } else {
            Toastr::error("Some error occured");
        }
        return back();
    }

    public function category_delete(Request $request)
    {
        $cate = BlogCategory::find($request->id);
        $cate->delete();
        Toastr::success("Deleted successfully");
        return back();
    }
    public function delete(Request $request)
    {
        $cate = BlogPost::find($request->id);
        $cate->delete();
        Toastr::success("Deleted successfully");
        return back();
    }
    public function category_edit(Request $request)
    {
        $category = BlogCategory::find($request->id);
        return view('admin-views.blog.category_edit', compact('category'));
    }
    public function edit(Request $request)
    {
        $post = BlogPost::find($request->id);
        $type = $post->type;
        $categories = BlogCategory::where('status', 1)->where('type', $type)->get();
        return view('admin-views.blog.edit', compact('post', 'categories'));
    }
    public function category_update(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'max:191',
                Rule::unique('blog_categories')->ignore($request->id),
            ],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $category =  BlogCategory::find($request->id);
        $category->image = $request->has('image') ? Helpers::update('blog/category/', $category->image, 'png', $request->file('image')) : $category->image;
        $category->name = $request->name;
        $category->type = $request->type;
        $saved =   $category->update();

        Toastr::success('Category updated successfully');

        return back();
    }
}
