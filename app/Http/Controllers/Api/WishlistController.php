<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToWishlistRequest;
use App\Http\Resources\WishlistResource;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    protected $wishlistService;

    // Dependency Injection
    public function __construct(WishlistService $wishlistService)
    {
        $this->wishlistService = $wishlistService;
    }

    public function index(Request $request)
    {
        $items = $this->wishlistService->getUserWishlist($request->user());
        return WishlistResource::collection($items);
    }

    public function store(AddToWishlistRequest $request): JsonResponse
    {
        $this->wishlistService->addToWishlist(
            $request->user(), 
            $request->validated()['product_id']
        );

        return response()->json(['message' => 'Product added to wishlist'], 201);
    }

    public function destroy(Request $request, $productId): JsonResponse
    {
        $deleted = $this->wishlistService->removeFromWishlist($request->user(), $productId);

        if (!$deleted) {
            return response()->json(['message' => 'Item not found in wishlist'], 404);
        }

        return response()->json(['message' => 'Product removed from wishlist'], 200);
    }
}