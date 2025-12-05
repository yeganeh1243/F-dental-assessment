<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_available_products(): void
    {
        // retrieving available products"
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price', 'description']
                ]
            ]);
    }

    public function test_user_can_register_and_login_to_get_token(): void
    {
        // user registration and login 
        
        // 1. Test Register
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);
        
        $response->assertCreated()
            ->assertJsonStructure(['token']);

        // 2. Test Login
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'new@example.com',
            'password' => 'password'
        ]);

        $loginResponse->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_user_can_add_product_to_wishlist(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/wishlist', ['product_id' => $product->id]);

        $response->assertCreated();

        // Verify it actually hit the DB
        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id
        ]);
    }

    public function test_user_can_view_their_wishlist(): void
    {
        $user = User::factory()->create();
        $products = Product::factory()->count(2)->create();

        // Pre-populate the list
        $user->wishlists()->create(['product_id' => $products[0]->id]);
        $user->wishlists()->create(['product_id' => $products[1]->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/wishlist');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_remove_product_from_wishlist(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        // Seed the relationship first
        $user->wishlists()->create(['product_id' => $product->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/wishlist/{$product->id}");

        $response->assertOk();

        // Ensure physical deletion
        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id
        ]);
    }

    public function test_adding_duplicate_product_is_idempotent(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // 1. First add
        $this->actingAs($user, 'sanctum')->postJson('/api/wishlist', ['product_id' => $product->id]);

        // 2. User double-clicks (simulate race condition/duplicate request)
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/wishlist', ['product_id' => $product->id]);

        // Should success (200/201), not explode with 500 error
        $response->assertSuccessful();

        // DB Integrity check: Should still only be 1 record
        $this->assertDatabaseCount('wishlists', 1);
    }

    public function test_cannot_add_non_existent_product(): void
    {
        $user = User::factory()->create();

        // Try adding ID 9999
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/wishlist', ['product_id' => 9999]);

        // Fail early via validation rules
        $response->assertUnprocessable(); 
        $response->assertJsonValidationErrors(['product_id']);
    }

    public function test_guests_cannot_access_wishlist_endpoints(): void
    {
        // Security check: No actingAs() here
        $this->getJson('/api/wishlist')->assertUnauthorized();
        $this->postJson('/api/wishlist', ['product_id' => 1])->assertUnauthorized();
    }
}