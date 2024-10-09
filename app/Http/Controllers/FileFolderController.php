<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileFolderRequest;
use App\Http\Requests\UpdateFileFolderRequest;
use App\Models\FileFolder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class FileFolderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFileFolderRequest $request)
    {
        $root = FileFolder::getRootOfAuthUser();

        $validated = $request->validate([
            'file' => 'required',
            'fileName' => 'required',
        ]);

        // extract base64 encoded file
        $binaryFile = base64_decode($validated['file']);
        $tempFileName = uniqid();
        $path = Storage::disk('local')->put($tempFileName, $binaryFile);

        $subFolder = FileFolder::create([
            'name' => $validated['fileName'], 
            'type' => 'file',
            'user_id' => Auth::id(),
        ]);
        $subFolder->appendToNode($root)->save();

        // return $rootFolder and $path in response
        return response()->json([
            'root' => $root,
            'subFolder' => $subFolder,
            'path' => $path,
            'decendents' => $descendants = $root->descendants()->get()
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(FileFolder $fileFolder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFileFolderRequest $request, FileFolder $fileFolder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FileFolder $fileFolder)
    {
        //
    }
}