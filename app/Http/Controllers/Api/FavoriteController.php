<?php

namespace App\Http\Controllers\Api;

use App\Models\UserFavorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends BaseController
{
    public function index(Request $request)
    {
        $favorites = UserFavorite::with('product.images')
                                ->where('user_id', $request->user()->id)
                                ->get();
                   
        return $this->sendResponse($favorites, 'Favorite items retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        // Check if product already in favorites
        $existingItem = UserFavorite::where('user_id', $request->user()->id)
                                  ->where('product_id', $request->product_id)
                                  ->first();

        if ($existingItem) {
            return $this->sendError('Product already in favorites.', [], 400);
        }

        $favorite = UserFavorite::create([
            'user_id' => $request->user()->id,
            'product_id' => $request->product_id
        ]);

        return $this->sendResponse($favorite->load('product.images'), 'Product added to favorites successfully.');
    }

    public function destroy($id)
    {
        $favoriteItem = UserFavorite::where('id', $id)
                                   ->where('user_id', request()->user()->id)
                                   ->first();
        
        if (is_null($favoriteItem)) {
            return $this->sendError('Favorite item not found.');
        }

        $favoriteItem->delete();
        return $this->sendResponse([], 'Product removed from favorites successfully.');
    }

    public function clear(Request $request)
    {
        UserFavorite::where('user_id', $request->user()->id)->delete();
        return $this->sendResponse([], 'Favorites cleared successfully.');
    }
}
