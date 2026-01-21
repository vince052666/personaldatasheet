<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CheckPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->user = User::factory()->create();
    }

    public function test_allows_access_with_correct_permission()
    {
        $this->user->givePermissionTo('view-pds');
        $this->actingAs($this->user);

        $request = Request::create('/test', 'GET');
        $middleware = new CheckPermission();

        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'view-pds');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_denies_access_without_permission()
    {
        $this->actingAs($this->user);

        $request = Request::create('/test', 'GET');
        $middleware = new CheckPermission();

        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'admin-only');

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_allows_access_with_any_of_multiple_permissions()
    {
        $this->user->givePermissionTo('view-pds');
        $this->actingAs($this->user);

        $request = Request::create('/test', 'GET');
        $middleware = new CheckPermission();

        $response = $middleware->handle($request, function () {
            return response('OK');
        }, 'view-pds|edit-pds');

        $this->assertEquals('OK', $response->getContent());
    }
}
