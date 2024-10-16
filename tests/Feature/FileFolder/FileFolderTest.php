<?php

namespace Tests\Feature\FileFolder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\Fluent\AssertableJson;

use App\Models\User;

class FileFolderTest extends TestCase
{
    // use RefreshDatabase;

    protected static $user1, $user2, $user3;

    /**
     * getRootNode
     */
    public function testGetRootNode(): void
    {
        self::$user1 = User::factory()->create();

        $response = $this->actingAs(self::$user1)
            ->get('/api/file-folders/get-root');
        Log::info('testGetRootNode response: ' . $response->getContent());

        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('root', fn (AssertableJson $json) =>
                    $json->where('user_id', self::$user1->id)
                        ->where('name', 'Home')
                        ->where('parent_id', null)
                        ->where('_lft', 1)
                        ->where('_rgt', 2)
                        ->where('children', [])
                        ->etc()
                    )
                ->has('children')
        );
    }

    /**
     * createNode
     * user1 creates a new folder under root
     */
    public function testCreateNode(): void
    {
        self::$user2 = User::factory()->create();

        Log::info('inside testCreateNode, user2: ' . self::$user2->id . ' and user1 is ' . self::$user1->id);

        $response = $this->actingAs(self::$user1)
            ->postJson('/api/file-folders/create-node', [
                'parent_id' => 1,
                'type' => 'folder',
                'name' => 'User1Folder'
            ]);

        Log::info('testCreateNode response: ' . $response->getContent());

        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('newNode', fn (AssertableJson $json) =>
                    $json->where('user_id', self::$user1->id)
                        ->where('name', 'User1Folder')
                        ->where('parent_id', 1)
                        ->where('_lft', 2)
                        ->where('_rgt', 3)
                        ->where('children', [])
                        ->etc()
                    )
                ->has('descendents')
                ->etc()
        );
    }
}
