<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\FileMetadata;
use Illuminate\Support\Str;

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

            // create metadata
            FileMetadata::create([
                'file_folder_id' => $root->id,
                'uri' => 'home',
                'size' => 0,
                'mime_type' => 'folder',
            ]);
        }

        return $root;
    }

    public static function getAncestors($nodeId)
    {
        // check access
        $user_id = self::hasAccess($nodeId);
        if(!$user_id) {
            Log::info('No access to node');
            return ['status' => false, 'message' => 'No access to node'];
        }

        $node = FileFolder::where('id', $nodeId)
            ->first();
        if (!$node) {
            return ['status' => false, 'message' => 'Node not found'];
        }

        return $node->ancestors()->get();
    }

    /**
     * return all descendents of a node
     */
    public static function getDescendents($nodeId)
    {
        // check access
        $user_id = self::hasAccess($nodeId);
        if(!$user_id) {
            Log::info('No access to node');
            return ['status' => false, 'message' => 'No access to node'];
        }

        $node = FileFolder::where('id', $nodeId)
            ->first();
        if (!$node) {
            return ['status' => false, 'message' => 'Node not found'];
        }

        return $node->descendants()->get();
    }

    /**
     * returns immediate children of a node
     */
    public static function getChildren($nodeId)
    {
        Log::info('inside Model getChildren with nodeId: ' . $nodeId);

        // check if node exists
        $node = FileFolder::where('id', $nodeId)
            ->first();
        if (!$node) {
            return ['status' => false, 'message' => 'Node not found'];
        }

        // check access
        $user_id = self::hasAccess($nodeId);
        if(!$user_id) {
            Log::info('No access to node');
            return ['status' => false, 'message' => 'No access to node'];
        }

        return $node->children()->get();
    }

    /**
     * hasAccess
     * @params $nodeId
     * checks if user has access to a node
     * or
     * if node is shared with user
     */

    public static function hasAccess($nodeId)
    {
        // check if node exists
        $node = FileFolder::where('id', $nodeId)
            ->first();
        if (!$node) {
            return false;
        }

        // simple case, the logged-in user is owner of file
        if ($node->user_id === Auth::id()) {
            return $node->user_id;
        }

        // complex case, when node is made available by sharing

        // get ancestors to this node
        $ancestorIds = [$node->id]; // pre-fill with current node id
        $ancestors = $node->ancestors()->get()->toArray();
        foreach ($ancestors as $ancestor) {
            $ancestorIds[] = $ancestor['id'];
        }
        // check if shared
        $sharedWith = FileFolderShare::whereIn('file_folder_id', $ancestorIds)
            ->where('user_id', Auth::id())
            ->first();

        if (!$sharedWith) {
            return false;
        }

        return $node->user_id;
    }

    public static function createNode($name, $type, $parentId)
    {
        // check hasAccess
        $user_id = self::hasAccess($parentId);
        if(!$user_id) {
            return ['status' => false, 'message' => 'No access to parent node'];
        }
        $parentNode = FileFolder::where('id', $parentId)->first();

        $newNode = FileFolder::create([
            'name' => $name,
            'type' => $type,
            'user_id' => $user_id, // owner of parent node
            'parent_id' => $parentId
        ]);

        // create metadata
        FileMetadata::create([
            'file_folder_id' => $newNode->id,
            'uri' => $newNode->name,
            'size' => 0,
            'mime_type' => 'folder',
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
            return ['status' => false, 'message' => 'Node not found'];
        }

        // check access
        $user_id = self::hasAccess($nodeId);
        if(!$user_id) {
            Log::info('No access to node');
            return ['status' => false, 'message' => 'No access to node'];
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
            return ['status' => false, 'message' => 'Node not found'];
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
        // check node exists
        $node = FileFolder::where('id', $nodeId)
            ->first();
        if (!$node) {
            return ['status' => false, 'message' => 'Node not found'];
        }

        // check if user exists
        $user = User::where('email', $emailId)->first();
        if (!$user) {
            return ['status' => false, 'message' => 'User not found'];
        }

        // check access
        $user_id = self::hasAccess($nodeId);
        if(!$user_id) {
            Log::info('No access to node');
            return ['status' => false, 'message' => 'No access to node'];
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
        // check node exists
        $node = FileFolder::where('id', $nodeId)
            ->first();
        if (!$node) {
            return ['status' => false, 'message' => 'Node not found'];
        }

        // check if user exists
        $user = User::where('id', $userId)->first();
        if (!$user) {
            return ['status' => false, 'message' => 'User not found'];
        }

        // check access
        $user_id = self::hasAccess($nodeId);
        if(!$user_id) {
            Log::info('No access to node');
            return ['status' => false, 'message' => 'No access to node'];
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

    /**
     * getHubRoot
     */
    public static function getHubRoot()
    {
        $sharedItems = FileFolderShare::where('user_id', Auth::id())
            ->get();
        $fileOrFolderIds = array_column($sharedItems->toArray(), 'file_folder_id');
        $sharedItems = FileFolder::whereIn('id', $fileOrFolderIds)->get()->toArray();
        return [
            'root' => [ // virtual root
                'id' => uniqid(),
                'name' => 'Hub',
                'text' => 'Hub',
                'type' => 'folder',
                'user_id' => Auth::id(),
                'parent_id' => null,
                'children' => $sharedItems,
            ],
        ];
    }

    /**
     * download
     * @params $nodeId
     */
    public static function download($nodeId)
    {
        Log::info('inside Model download with nodeId: ' . $nodeId);
        // check access
        $user_id = self::hasAccess($nodeId);
        if(!$user_id) {
            Log::info('No access to node');
            return ['status' => false, 'message' => 'No access to node'];
        }

        $node = FileFolder::where('id', $nodeId)
            ->first();
        if (!$node) {
            return ['status' => false, 'message' => 'Node not found'];
        }

        $metadata = FileMetadata::where('file_folder_id', $node->id)
            ->first();
        if (!$metadata) {
            return ['status' => false, 'message' => 'Metadata not found'];
        }

        Log::info('metadata: ' . print_r($metadata, true));

        $contents = Storage::disk('local')->get($metadata->uri);

        return [
            'status' => true,
            'message' => 'Downloaded successfully', 
            'file' => base64_encode($contents),
            'fileName' => $node->name,
        ];
    }

    /**
     * deleteNode
     * @params $nodeId
     */
    public static function deleteNode($nodeId)
    {
        // check node exists
        $node = FileFolder::where('id', $nodeId)
            ->first();
        if (!$node) {
            return ['status' => false, 'message' => 'Node not found'];
        }

        // check access
        $user_id = self::hasAccess($nodeId);
        if(!$user_id) {
            Log::info('No access to node');
            return ['status' => false, 'message' => 'No access to node'];
        }

        if ($node->type === 'folder') {
            // delete all descendents
            $descendents = $node->descendants()->get();
            foreach ($descendents as $descendent) {
                $metadata = FileMetadata::where('file_folder_id', $descendent->id)
                    ->first();
                if ($metadata) {
                    Storage::disk('local')->delete($metadata->uri);
                    $metadata->delete();
                }
                $descendent->delete();
            }
        } else {
            // delete file
            $metadata = FileMetadata::where('file_folder_id', $node->id)
                ->first();
            if ($metadata) {
                Storage::disk('local')->delete($metadata->uri);
                $metadata->delete();
            }
        }

        $node->delete();

        return ['status' => true, 'message' => 'Deleted successfully'];
    }

    /**
     * getMetadata
     * @params $nodeId
     */
    public static function getMetadata($nodeId)
    {

        // check node exists
        $node = FileFolder::where('id', $nodeId)
            ->first();
        if (!$node) {
            return ['status' => false, 'message' => 'Node not found'];
        }

        // check access
        $user_id = self::hasAccess($nodeId);
        if(!$user_id) {
            Log::info('No access to node');
            return ['status' => false, 'message' => 'No access to node'];
        }

        $metadata = FileMetadata::where('file_folder_id', $node->id)
            ->first();
        if (!$metadata) {
            return ['status' => false, 'message' => 'Metadata not found'];
        }

        return $metadata;
    }

    /**
     * getPublicLink
     * @params $nodeId
     */
    public static function getPublicLink($nodeId)
    {
        // check node exists
        $node = FileFolder::where('id', $nodeId)
            ->first();
        if (!$node) {
            return ['status' => false, 'message' => 'Node not found'];
        }

        // check access
        $user_id = self::hasAccess($nodeId);
        if(!$user_id) {
            Log::info('No access to node');
            return ['status' => false, 'message' => 'No access to node'];
        }

        $metadata = FileMetadata::where('file_folder_id', $node->id)
            ->first();
        if (!$metadata) {
            return ['status' => false, 'message' => 'Metadata not found'];
        }
        $metadata->public_token = Str::random(32);
        $metadata->save();

        return [
            'status' => true,
            'message' => 'Public link generated',
            'publicLink' => env('FRONTEND_URL') . '/public/' . $metadata->public_token,
        ];
    }

    /**
     * publicDownload
     * @params $token
     */
    public static function publicDownload($token)
    {
        $metadata = FileMetadata::where('public_token', $token)
            ->first();
        if (!$metadata) {
            return ['status' => false, 'message' => 'Metadata not found'];
        }

        $node = FileFolder::where('id', $metadata->file_folder_id)
            ->first();
        if (!$node) {
            return ['status' => false, 'message' => 'Node not found'];
        }

        $contents = Storage::disk('local')->get($metadata->uri);

        return [
            'status' => true,
            'message' => 'Downloaded successfully', 
            'file' => base64_encode($contents),
            'fileName' => $node->name,
        ];
    }
}
