<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileFolderRequest;
use App\Http\Requests\UpdateFileFolderRequest;
use Illuminate\Http\Request;
use App\Models\FileFolder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;

class FileFolderController extends Controller
{

    public function __construct()
    {
        Log::info('Inside FileFolderController constructor');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * @GetRootNode
     */
    public function getRootNode()
    {
        $root = FileFolder::getRootOfAuthUser();
        return response()->json([
            'root' => $root,
            'children' => $descendants = $root->children()->get()
        ]);
    }

    /**
     * create node
     * @params $parentId
     * @params $type
     */
    public function createNode(Request $request)
    {
        Log::info('Inside createNode of FileFolderController');
        $validated = $request->validate([
            'parent_id' => 'required',
            'type' => 'required',
            'name' => 'required',
        ]);

        $createResponse = FileFolder::createNode($validated['name'], $validated['type'], $validated['parent_id']);
        // return $rootFolder and $path in response
        return response()->json($createResponse);
    }

    /**
     * get ancestors
     * @params $nodeId
     */
    public function getAncestors($node_id)
    {
        $ancestors = FileFolder::getAncestors($node_id);
        return response()->json($ancestors);
    }

    /**
     * get descendents
     * @params $nodeId
     */
    public function getDescendents($node_id)
    {
        $descendents = FileFolder::getDescendents($node_id);
        return response()->json($descendents);
    }

    /**
     * get children
     * @params $nodeId
     */
    public function getChildren($node_id)
    {
        $children = FileFolder::getChildren($node_id);
        return response()->json($children);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFileFolderRequest $request)
    {
        $validated = $request->validate([
            'file' => 'required',
            'fileName' => 'required',
            'parent_id' => 'required',
        ]);

        // try to find the parent node
        $parentNode = FileFolder::where('id', $validated['parent_id'])
            ->where('user_id', Auth::id())
            ->first();
        if (!$parentNode) {
            return response()->json([
                'message' => 'Parent node not found'
            ], 404);
        }

        // extract base64 encoded file
        $binaryFile = base64_decode($validated['file']);
        $tempFileName = uniqid();
        $path = Storage::disk('local')->put($tempFileName, $binaryFile);

        $subFolder = FileFolder::create([
            'name' => $validated['fileName'], 
            'type' => 'file',
            'user_id' => Auth::id(),
            'parent_id' => $validated['parent_id'],
        ]);
        $subFolder->appendToNode($parentNode)->save();

        // return $rootFolder and $path in response
        return response()->json([
            'parentNode' => $parentNode,
            'subFolder' => $subFolder,
            'path' => $path,
            'decendents' => $descendants = $parentNode->descendants()->get()
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(FileFolder $fileFolder)
    {
        Log::info('Inside show of FileFolderController');
       // Log the entire request data
        Log::info('Incoming request:' . print_r($fileFolder->id, true));

        $children = FileFolder::getChildren($fileFolder->id);
        return response()->json([
            'descendents' => $children
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFileFolderRequest $request, FileFolder $fileFolder)
    {
        Log::info('Inside update of FileFolderController');
        $validated = $request->validate([
            'id' => 'required',
            'text' => 'required',
        ]);

        Log::info('Incoming request:' . print_r($validated, true));

        $updateResponse = FileFolder::updateNode($validated['id'], $validated['text']);
        // return $rootFolder and $path in response
        return response()->json($updateResponse);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FileFolder $fileFolder)
    {
        //
    }

    /**
     * get share
     * @params $nodeId
     */
    public function getShare($node_id)
    {
        $share = FileFolder::getShare($node_id);
        return response()->json($share);
    }

    /**
     * add share
     */
    public function addShare($node_id, Request $request)
    {
        Log::info('Inside addShare of FileFolderController');
        $validated = $request->validate([
            'email_id' => 'required|email',
        ]);

        $email_id = $validated['email_id'];
        $share = FileFolder::addShare($node_id, $email_id);
        return response()->json($share);
    }

    /**
     * delete share
     */
    public function deleteShare($node_id, Request $request)
    {
        Log::info('Inside deleteShare of FileFolderController');
        $validated = $request->validate([
            'user_id' => 'required',
        ]);

        $share = FileFolder::deleteShare($node_id, $validated['user_id']);
        return response()->json($share);
    }

    /**
     * get hub root
     */
    public function getHubRoot()
    {
        $hubRoot = FileFolder::getHubRoot();
        return response()->json($hubRoot);
    }
}
