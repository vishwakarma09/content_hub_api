<?php

namespace Tests\Feature\FileFolder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\Fluent\AssertableJson;

use App\Models\User;
use App\Models\FileMetadata;

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

    /**
     * getAncestors
     */
    public function testGetAncestors(): void
    {
        $response = $this->actingAs(self::$user1)
            ->get('/api/file-folders/2/ancestors');
        Log::info('testGetAncestors response: ' . $response->getContent());

        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(1)
                    ->first(fn (AssertableJson $json) =>
                        $json->where('user_id', self::$user1->id)
                            ->where('name', 'Home')
                            ->where('parent_id', null)
                            ->where('_lft', 1)
                            ->where('_rgt', 4)
                            ->etc()
                    )
        );
    }

    /**
     * getDescendents
     */
    public function testGetDescendents(): void
    {
        $response = $this->actingAs(self::$user1)
            ->get('/api/file-folders/1/descendents');
        Log::info('testGetDescendents response: ' . $response->getContent());

        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(1)
                    ->first(fn (AssertableJson $json) =>
                        $json->where('user_id', self::$user1->id)
                            ->where('name', 'User1Folder')
                            ->where('parent_id', 1)
                            ->where('_lft', 2)
                            ->where('_rgt', 3)
                            ->etc()
                    )
        );
    }

    /**
     * getChildren
     */
    public function testGetChildren(): void
    {
        $response = $this->actingAs(self::$user1)
            ->get('/api/file-folders/1/children');
        Log::info('testGetChildren response: ' . $response->getContent());

        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(1)
                    ->first(fn (AssertableJson $json) =>
                        $json->where('user_id', self::$user1->id)
                            ->where('name', 'User1Folder')
                            ->where('parent_id', 1)
                            ->where('_lft', 2)
                            ->where('_rgt', 3)
                            ->etc()
                    )
        );
    }

    /**
     * getShare
     */
    public function testGetShareBeforeSharingNode(): void
    {
        $response = $this->actingAs(self::$user1)
            ->get('/api/file-folders/2/share');
        Log::info('testGetShare response: ' . $response->getContent());

        $response
            ->assertStatus(200)
            ->assertJson([]);
    }

    /**
     * addShare
     */
    public function testAddShare(): void
    {
        self::$user2 = User::factory()->create();

        $response = $this->actingAs(self::$user1)
            ->postJson('/api/file-folders/2/share', [
                'email_id' => self::$user2->email,
            ]);
        Log::info('testAddShare response: ' . $response->getContent());
        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('status', true)
                    ->where('message', 'Shared successfully')
            );

        // get share after sharing
        $response = $this->actingAs(self::$user1)
            ->get('/api/file-folders/2/share');
        Log::info('testGetShare response: ' . $response->getContent());

        $response
            ->assertJson(fn (AssertableJson $json) =>
            $json
                ->has(1)
                ->first(fn (AssertableJson $json) =>
                    $json->where('id', self::$user2->id)
                        ->where('name', self::$user2->name)
                        ->where('email', self::$user2->email)
                        ->etc()
                )
            );
    }

    /**
     * deleteShare
     */
    public function testDeleteShare(): void
    {
        $response = $this->actingAs(self::$user1)
            ->deleteJson('/api/file-folders/2/share', [
                'user_id' => self::$user2->id,
            ]);
        Log::info('testDeleteShare response: ' . $response->getContent());
        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('status', true)
                    ->where('message', 'Deleted share successfully')
            );

        // get share after unsharing
        $response = $this->actingAs(self::$user1)
            ->get('/api/file-folders/2/share');
        Log::info('testGetShare response: ' . $response->getContent());

        $response
            ->assertJson([]);
    }

    /**
     * getHubRoot
     */
    public function testGetHubRoot(): void
    {
        $response = $this->actingAs(self::$user1)
            ->get('/api/file-folders/get-hub-root');
        Log::info('testGetHubRoot response: ' . $response->getContent());

        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('root', fn (AssertableJson $json) =>
                    $json->where('id', 'hub-' . self::$user1->id)
                        ->where('name', 'Hub')
                        ->where('parent_id', null)
                        ->where('user_id', self::$user1->id)
                        ->where('children', [])
                        ->etc()
                    )
                ->etc()
        );
    }

    /**
     * getPublicLink
     */
    public function testGetPublicLink(): void
    {
        $response = $this->actingAs(self::$user1)
            ->get('/api/file-folders/2/public-link');
        Log::info('testGetPublicLink response: ' . $response->getContent());

        $metadata = FileMetadata::where('file_folder_id', 2)->first();
        $publicLink = env('FRONTEND_URL') . '/public/' . $metadata->public_token;
        Log::info('publicLink: ' . $publicLink);

        $response
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('status', true)
                    ->where('message', 'Public link generated')
                    ->where('publicLink', $publicLink)
            );
    }

    /**
     * publicDownload
     */
    public function testPublicDownload(): void
    {
        $metadata = FileMetadata::where('file_folder_id', 2)->first();
        $response = $this->get('/api/file-folders/public-download/' . $metadata->public_token);
        Log::info('testPublicDownload response: ' . $response->getContent());

        $response
            ->assertStatus(200);
    }

    /**
     * test sample
     */
    public function testSample(): void
    {
        $response = $this->actingAs(self::$user1)
            ->get('/api/file-folders/get-root');
        Log::info('testSample response: ' . $response->getContent());

        $response
            ->assertStatus(200);
    }
}
