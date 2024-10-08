<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileFolderRequest;
use App\Http\Requests\UpdateFileFolderRequest;
use App\Models\FileFolder;

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
        //
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
