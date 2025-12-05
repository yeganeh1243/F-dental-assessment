<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Collection;

class WishlistService
{
    public function getUserWishlist(User $user): Collection
    {
        // Eager load 'product' to avoid N+1 performance issues on the frontend list
        return Wishlist::with('product')
            ->where('user_id', $user->id)
            ->latest() 
            ->get();
    }

    public function addToWishlist(User $user, int $productId): Wishlist
    {
        // Handle race conditions/double-clicks gracefully using firstOrCreate
        return Wishlist::firstOrCreate([
            'user_id' => $user->id,
            'product_id' => $productId
        ]);
    }

    public function removeFromWishlist(User $user, int $productId): bool
    {
        // Cast to bool so the controller gets a simple true/false success state
        return (bool) Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->delete();
    }
}