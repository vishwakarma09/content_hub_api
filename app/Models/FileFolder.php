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

    protected $table = 'files_and_folders';

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
                'name' => 'Root',
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
}
