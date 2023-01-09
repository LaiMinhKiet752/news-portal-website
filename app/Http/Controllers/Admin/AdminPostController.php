<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Tag;
use App\Models\SubCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminPostController extends Controller
{
    public function show()
    {
        $posts = Post::with('rSubCategory.rCategory')->get();
        return view('admin.post_show', compact('posts'));
    }
    public function create()
    {
        $sub_categories = SubCategory::with('rCategory')->get();
        return view('admin.post_create', compact('sub_categories'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'post_title' => 'required',
            'post_detail' => 'required',
            'post_photo' => 'required|image|mimes:png,jpg,jpeg,gif'
        ]);

        $q = DB::select("SHOW TABLE STATUS LIKE 'posts'");
        $ai_id = $q[0]->Auto_increment;

        $now = time();
        $ext = $request->file('post_photo')->extension();
        $final_name = 'post_photo_' . $now . '.' . $ext;
        $request->file('post_photo')->move(public_path('uploads/'), $final_name);

        $post = new Post();
        $post->sub_category_id = $request->sub_category_id;
        $post->post_title = $request->post_title;
        $post->post_detail = $request->post_detail;
        $post->visitors = 1;
        $post->author_id = 0;
        $post->admin_id = Auth::guard('admin')->user()->id;
        $post->is_share = $request->is_share;
        $post->is_comment = $request->is_comment;
        $post->post_photo = $final_name;
        $post->save();

        if ($request->tags != '') {
            $tag_array_new = [];
            $tag_array = explode(',', $request->tags);
            for ($i = 0; $i < count($tag_array); $i++) {
                $tag_array_new[] = trim($tag_array[$i]);
            }
            $tag_array_new = array_values(array_unique($tag_array_new));
            for ($i = 0; $i < count($tag_array_new); $i++) {
                $tag = new Tag();
                $tag->post_id = $ai_id;
                $tag->tag_name = trim($tag_array_new[$i]);
                $tag->save();
            }
        }
        return redirect()->route('admin_post_show')->with('success', 'Data is added successfully!');
    }

    public function edit($id)
    {
        $sub_categories = SubCategory::with('rCategory')->get();
        $existing_tags = Tag::where('post_id', $id)->get();
        $post_single = Post::where('id', $id)->first();
        return view('admin.post_edit', compact('post_single', 'sub_categories', 'existing_tags'));
    }

    public function delete_tag($id, $id1)
    {
        $tag = Tag::where('id', $id)->first();
        $tag->delete();
        return redirect()->route('admin_post_edit', $id1)->with('success', 'Data is deleted successfully!');
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'post_title' => 'required',
            'post_detail' => 'required'
        ]);
        $post = Post::where('id', $id)->first();
        if ($request->hasFile('post_photo')) {
            $request->validate([
                'post_photo' => 'image|mimes:png,jpg,jpeg,gif'
            ]);
            unlink(public_path('uploads/' . $post->post_photo));
            $now = time();
            $ext = $request->file('post_photo')->extension();
            $final_name = 'post_photo_' . $now . '.' . $ext;
            $request->file('post_photo')->move(public_path('uploads/'), $final_name);
            $post->post_photo = $final_name;
        }

        $post->sub_category_id = $request->sub_category_id;
        $post->post_title = $request->post_title;
        $post->post_detail = $request->post_detail;
        $post->visitors = 1;
        $post->author_id = 0;
        $post->admin_id = Auth::guard('admin')->user()->id;
        $post->is_share = $request->is_share;
        $post->is_comment = $request->is_comment;
        $post->update();

        if ($request->tags != '') {
            $tags_array = explode(',', $request->tags);
            for ($i = 0; $i < count($tags_array); $i++) {
                $total = Tag::where('post_id', $id)->where('tag_name', trim($tags_array[$i]))->count();
                if (!$total) {
                    $tag = new Tag();
                    $tag->post_id = $id;
                    $tag->tag_name = trim($tags_array[$i]);
                    $tag->save();
                }
            }
        }
        return redirect()->route('admin_post_show')->with('success', 'Data is updated successfully!');
    }

    public function delete($id)
    {
        $post = Post::where('id', $id)->first();
        unlink(public_path('uploads/' . $post->post_photo));
        $post->delete();
        Tag::where('post_id', $id)->delete();
        return redirect()->route('admin_post_show')->with('success', 'Data is deleted successfully!');
    }
}
