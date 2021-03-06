<?php

namespace App\Http\Controllers;

use App\Post;
use Illuminate\support\Facades\Storage;
use Illuminate\Http\Request;

class PostsController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //Pass execeptions for the authentication middleware to allow pages to be publicly open to non-authenticated users
        $this->middleware('auth', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //We are using eloquent the object relational mapper
        $post = Post::orderBy('created_at', 'desc')->paginate(10);

        //Display all posts
        // $post = Post::all();

        //Take 1 Post From Array 
        // $post = Post::orderBy('title', 'desc')->take(1)->get();

        //Write A Custom Query For Index Page - If we was to design a specific index page and return a certain kind of post
        // Post::where('title', 'Post Two')->get();

        return view('posts.index')->with('posts', $post);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('posts.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required',
            'body' => 'required',
            'cover_image' => 'image|nullable|max:1999'
        ]);

        //Handle File Upload
        if($request->hasFile('cover_image')){

            //Get A Filename With The Extension
            $filenameWithExt = $request->file('cover_image')->getClientOriginalName();
            //Get Just Filename
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            //Get Just Ext
            $extension = $request->file('cover_image')->getClientOriginalExtension();
            //Filename To Store - Save as originalfilename_timestamp.ext
            $fileNameToStore = $filename.'_'.time().'.'.$extension;
            //Upload Image
            $path = $request->file('cover_image')->storeAs('public/cover_images', $fileNameToStore);

        }else{

            $fileNameToStore = 'noimage.jpg';

        }

        //Once Validation is passed - Create A New Post Model Obj
        $post = new Post();
        $post->title = $request->input('title');
        $post->body = $request->input('body');
        //this will grab the currently logged in user and put it into the id
        $post->user_id = auth()->user()->id;
        $post->cover_image = $fileNameToStore;
        $post->save();

        return redirect('/posts')->with('success', 'Post Created');
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //Returns A Single Post
        $post =  Post::find($id);
        return view('posts.show')->with('post', $post);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $post =  Post::find($id);

        //Check For Correct User (We do not want to show this edit view to other Users apart from THEE USER)
        if(auth()->user()->id !== $post->user_id){
            return redirect('/posts')->with('error', 'Unauthorised page');  
        }

        return view('posts.edit')->with('post', $post);   
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'title' => 'required',
            'body' => 'required'
        ]);

        //Handle File Upload
        if($request->hasFile('cover_image')){

            //Get A Filename With The Extension
            $filenameWithExt = $request->file('cover_image')->getClientOriginalName();
            //Get Just Filename
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            //Get Just Ext
            $extension = $request->file('cover_image')->getClientOriginalExtension();
            //Filename To Store - Save as originalfilename_timestamp.ext
            $fileNameToStore = $filename.'_'.time().'.'.$extension;
            //Upload Image
            $path = $request->file('cover_image')->storeAs('public/cover_images', $fileNameToStore);

        }

        //Once Validation is passed - create a new Post Model Obj
        $post = Post::find($id);
        $post->title = $request->input('title');
        $post->body = $request->input('body');
        if($request->hasFile('cover_image')){
            $post->cover_image = $fileNameToStore;
        }
        $post->save();

        return redirect('/posts')->with('success', 'Post Updated');
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $post = Post::find($id);

         if(auth()->user()->id !== $post->user_id){
            return redirect('/posts')->with('error', 'Unauthorised Delete Action');  
        }

        if($post->cover_image != 'noimage.jpg'){

            //Delete Image
            Storage::delete('public/cover_images/' . $post->cover_image);

        }

        $post->delete();
        return redirect('/posts')->with('success', 'Post Deleted');
    }
}
