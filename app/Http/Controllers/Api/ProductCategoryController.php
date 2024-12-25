<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductCat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductCategoryController extends BaseController
{
    public function index()
    {
        $categories = ProductCat::withCount('products')->get();
        return $this->sendResponse($categories, 'Product categories retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'desc' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $category = ProductCat::create($request->all());
        return $this->sendResponse($category, 'Product category created successfully.');
    }

    public function show($id)
    {
        $category = ProductCat::with('products')->find($id);
        
        if (is_null($category)) {
            return $this->sendError('Product category not found.');
        }
        
        return $this->sendResponse($category, 'Product category retrieved successfully.');
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'desc' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $category = ProductCat::find($id);
        
        if (is_null($category)) {
            return $this->sendError('Product category not found.');
        }

        $category->update($request->all());
        return $this->sendResponse($category, 'Product category updated successfully.');
    }

    public function destroy($id)
    {
        $category = ProductCat::find($id);
        
        if (is_null($category)) {
            return $this->sendError('Product category not found.');
        }

        if ($category->products()->count() > 0) {
            return $this->sendError('Cannot delete category with associated products.', [], 400);
        }

        $category->delete();
        return $this->sendResponse([], 'Product category deleted successfully.');
    }
}
