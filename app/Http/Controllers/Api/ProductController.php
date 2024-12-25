<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends BaseController
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images', 'sizes', 'colors']);

        if ($request->has('category')) {
            $query->where('cat_id', $request->category);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('desc', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(10);
        return $this->sendResponse($products, 'Products retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'desc' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'qty' => 'required|integer|min:0',
            'cat_id' => 'required|exists:product_cats,id',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sizes' => 'required|array',
            'sizes.*' => 'required|string',
            'colors' => 'required|array',
            'colors.*' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $product = Product::create($request->all());

        if ($request->hasFile('images')) {
            foreach($request->file('images') as $image) {
                $filename = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/products'), $filename);
                
                ProductImage::create([
                    'product_id' => $product->id,
                    'name' => 'uploads/products/' . $filename
                ]);
            }
        }

        foreach($request->sizes as $size) {
            $product->sizes()->create(['name' => $size]);
        }

        foreach($request->colors as $color) {
            $product->colors()->create(['name' => $color]);
        }

        return $this->sendResponse($product->load(['category', 'images', 'sizes', 'colors']), 'Product created successfully.');
    }

    public function show($id)
    {
        $product = Product::with(['category', 'images', 'sizes', 'colors', 'rates.user'])->find($id);
        
        if (is_null($product)) {
            return $this->sendError('Product not found.');
        }
        
        return $this->sendResponse($product, 'Product retrieved successfully.');
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'desc' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'qty' => 'required|integer|min:0',
            'cat_id' => 'required|exists:product_cats,id',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sizes' => 'nullable|array',
            'sizes.*' => 'required|string',
            'colors' => 'nullable|array',
            'colors.*' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $product = Product::find($id);
        
        if (is_null($product)) {
            return $this->sendError('Product not found.');
        }

        $product->update($request->all());

        if ($request->hasFile('images')) {
            // Delete old images
            foreach($product->images as $image) {
                if(file_exists(public_path($image->name))) {
                    unlink(public_path($image->name));
                }
            }
            $product->images()->delete();

            // Add new images
            foreach($request->file('images') as $image) {
                $filename = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/products'), $filename);
                
                ProductImage::create([
                    'product_id' => $product->id,
                    'name' => 'uploads/products/' . $filename
                ]);
            }
        }

        if ($request->has('sizes')) {
            $product->sizes()->delete();
            foreach($request->sizes as $size) {
                $product->sizes()->create(['name' => $size]);
            }
        }

        if ($request->has('colors')) {
            $product->colors()->delete();
            foreach($request->colors as $color) {
                $product->colors()->create(['name' => $color]);
            }
        }

        return $this->sendResponse($product->load(['category', 'images', 'sizes', 'colors']), 'Product updated successfully.');
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        
        if (is_null($product)) {
            return $this->sendError('Product not found.');
        }

        // Delete product images from storage
        foreach($product->images as $image) {
            if(file_exists(public_path($image->name))) {
                unlink(public_path($image->name));
            }
        }

        $product->delete();

        return $this->sendResponse([], 'Product deleted successfully.');
    }

    public function rate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|integer|min:1|max:5'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $product = Product::find($id);
        
        if (is_null($product)) {
            return $this->sendError('Product not found.');
        }

        $rate = $product->rates()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['value' => $request->value]
        );

        return $this->sendResponse($rate, 'Product rated successfully.');
    }
}
