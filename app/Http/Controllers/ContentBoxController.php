<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContentBoxRequest;
use App\Http\Requests\UpdateContentBoxRequest;
use App\Models\ContentBox;
use App\Models\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class ContentBoxController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ContentBox::class);
    }

    public function index()
    {
        $contentBoxes = ContentBox::all();
        return view('content-box.index', compact('contentBoxes'));
    }

    public function favoriteBoxes(){
        $contentBoxes = auth()->user()->favorites;
        return view('content-box.index', compact('contentBoxes'));
    }

    public function create()
    {
        return view('content-box.create');
    }

    public function store(StoreContentBoxRequest $request)
    {
        $data = $request->validated();
        $contentBox = new ContentBox();
        $contentBox->title = $data['title'];
        $contentBox->owner_id = Auth::user()->id;
        $contentBox->save();
        foreach ($data['files'] as $dataFile){
            $file = new File();
            $storedFile = $dataFile->store(Config::get('uploaded-files.directory'));
            $file->name = pathinfo($storedFile)['basename'];
            $file->content_box_id = $contentBox->id;
            $file->save();
            unset($file, $storedFile);
        }
        return redirect(route('content-box.index'))->with('success', true);
    }

    public function show(ContentBox $contentBox)
    {
        $favorited = false;
        if($contentBox->favoritedBy->contains(auth()->user()->id)){
            $favorited = true;
        }
        return view('content-box.show', compact('contentBox', 'favorited'));
    }

    public function edit(ContentBox $contentBox)
    {
        return view('content-box.edit', compact('contentBox'));
    }

    public function update(UpdateContentBoxRequest $request, ContentBox $contentBox)
    {
        $data = $request->validated();
        $contentBox->title = $data['title'];
        $contentBox->save();
        foreach ($data['files'] as $dataFile){
            $file = new File();
            $storedFile = $dataFile->store(Config::get('uploaded-files.directory'));
            $file->name = pathinfo($storedFile)['basename'];
            $file->content_box_id = $contentBox->id;
            $file->save();
            unset($file, $storedFile);
        }
        return redirect(route('content-box.edit', $contentBox->id))->with('success', true);
    }

    public function destroy(ContentBox $contentBox)
    {
        $filesInBox = $contentBox->files;
        $directory = Config::get('uploaded-files.directory');
        foreach ($filesInBox as $file){
            Storage::delete($directory . '/' . $file->name);
        }
        $contentBox->delete();
        return redirect(route('content-box.index'))->with('success', true);
    }

    public function downloadFile(File $file){
        $path = Config::get('uploaded-files.directory') . "/" . $file->name;
        return Storage::download($path);
    }
}
