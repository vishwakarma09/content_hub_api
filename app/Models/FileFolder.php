<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

use Illuminate\Support\Facades\Log;

class FileFolder extends Model
{
    use HasFactory, NodeTrait; // This trait comes from the kalnoy/nestedset package

    // protected $table = 'files_and_folders';

    protected $fillable = ['name', 'type', 'parent_id', '_lft', '_rgt', 'depth', 'user_id'];

    public static function getRootOfAuthUser()
    {
        $root = FileFolder::where('user_id', Auth::id())
            ->whereNull('parent_id')
            ->where('depth', 0)
            ->first();
        // create root if not exists
        if (!$root) {
            $root = FileFolder::create([
                'name' => 'Home',
                'type' => 'folder',
                'user_id' => Auth::id()
            ]);
        }

        return $root;
    }

    public static function getAncestors($nodeId)
    {
        Log::info('inside Model getAncestors with nodeId: ' . $nodeId);

        $node = FileFolder::where('id', $nodeId)
            ->where('user_id', Auth::id())
            ->first();

        return $node->ancestors()->get();
    }

    /**
     * return all descendents of a node
     */
    public static function getDescendents($nodeId)
    {
        Log::info('inside Model getDescendents with nodeId: ' . $nodeId);

        $node = FileFolder::where('id', $nodeId)
            ->where('user_id', Auth::id())
            ->first();

        return $node->descendants()->get();
    }

    /**
     * returns immediate children of a node
     */
    public static function getChildren($nodeId)
    {
        Log::info('inside Model getChildren with nodeId: ' . $nodeId);

        $node = FileFolder::where('id', $nodeId)
            ->where('user_id', Auth::id())
            ->first();

        return $node->children()->get();
    }

    public static function createNode($name, $type, $parentId)
    {
        $parentNode = FileFolder::where('id', $parentId)
            ->where('user_id', Auth::id())
            ->first();
        if (!$parentNode) {
            return response()->json([
                'message' => 'Parent node not found'
            ], 404);
        }

        $newNode = FileFolder::create([
            'name' => $name,
            'type' => $type,
            'user_id' => Auth::id(),
            'parent_id' => $parentId
        ]);

        return [
            'newNode' => $newNode,
            'descendents' => $descendants = $parentNode->descendants()->get(),
        ];
    }

    public static function updateNode($nodeId, $name)
    {
        $node = FileFolder::where('id', $nodeId)
            ->where('user_id', Auth::id())
            ->first();
        if (!$node) {
            return response()->json([
                'message' => 'Node not found'
            ], 404);
        }

        $node->name = $name;
        $node->save();

        return $node;
    }

    /**
     * getShare
     * @params $nodeId
     */
    public static function getShare($nodeId)
    {
        // validate ownership
        $node = FileFolder::where('id', $nodeId)
            ->where('user_id', Auth::id())
            ->first();
        if (!$node) {
            return response()->json([
                'message' => 'Node not found'
            ], 404);
        }

        // get ancestors to this node
        $ancestors = $node->ancestors()->get()->toArray();
        Log::info('ancestors: ' . print_r($ancestors, true));
        $ancestorIds = [$node->id]; // pre-fill with current node id
        foreach ($ancestors as $ancestor) {
            $ancestorIds[] = $ancestor['id'];
        }

        // get all users with whom ancestor nodes are shared
        $sharedWith = FileFolderShare::whereIn('file_folder_id', $ancestorIds)->get()->toArray();
        $users =  User::whereIn('id', array_column($sharedWith, 'user_id'))->get()->toArray();
        return $users;
    }

    /**
     * addShare
     * @params $nodeId, $userId
     */
    public static function addShare($nodeId, $emailId)
    {
        // validate ownership
        $node = FileFolder::where('id', $nodeId)
            ->where('user_id', Auth::id())
            ->first();
        if (!$node) {
            return ['status' => false, 'message' => 'Node not found'];
        }

        // check if user exists
        $user = User::where('email', $emailId)->first();
        if (!$user) {
            return ['status' => false, 'message' => 'User not found'];
        }

        // get ancestors to this node
        $ancestorIds = [$node->id]; // pre-fill with current node id
        $ancestors = $node->ancestors()->get()->toArray();
        foreach ($ancestors as $ancestor) {
            $ancestorIds[] = $ancestor['id'];
        }
        Log::info('ancestors: ' . print_r($ancestorIds, true));

        // check if already shared
        $sharedWith = FileFolderShare::whereIn('file_folder_id', $ancestorIds)
            ->where('user_id', $user->id)
            ->first();
        if ($sharedWith) {
            return ['status' => false, 'message' => 'Already shared with this user'];
        }

        Log::info('inside Model addShare with nodeId: ' . $nodeId . ' user id ' . $user->id);
        FileFolderShare::create([
            'file_folder_id' => $nodeId,
            'user_id' => $user->id
        ]);

        return ['status' => true, 'message' => 'Shared successfully'];
    }

    /**
     * deleteShare
     * @params $nodeId, $userId
     */
    public static function deleteShare($nodeId, $userId)
    {
        // validate ownership
        $node = FileFolder::where('id', $nodeId)
            ->where('user_id', Auth::id())
            ->first();
        if (!$node) {
            return ['status' => false, 'message' => 'Node not found'];
        }

        // check if user exists
        $user = User::where('id', $userId)->first();
        if (!$user) {
            return ['status' => false, 'message' => 'User not found'];
        }

        // get ancestors to this node
        $ancestorIds = [$node->id]; // pre-fill with current node id
        $ancestors = $node->ancestors()->get()->toArray();
        foreach ($ancestors as $ancestor) {
            $ancestorIds[] = $ancestor['id'];
        }
        Log::info('ancestors: ' . print_r($ancestorIds, true));

        // check if already shared
        $sharedWith = FileFolderShare::whereIn('file_folder_id', $ancestorIds)
            ->where('user_id', $user->id)
            ->first();
        if (!$sharedWith) {
            return ['status' => false, 'message' => 'Not shared with this user'];
        }

        Log::info('inside Model deleteShare with nodeId: ' . $nodeId . ' user id ' . $user->id);
        $sharedWith->delete();

        return ['status' => true, 'message' => 'Deleted share successfully'];
    }
}
