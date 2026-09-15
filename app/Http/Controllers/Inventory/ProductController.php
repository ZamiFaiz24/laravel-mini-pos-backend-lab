<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Product::query()
                ->with(['category', 'supplier'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0'],
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => $product->load(['category', 'supplier']),
        ], 201);
    }

    public function show(Product $product)
    {
        return response()->json([
            'data' => $product->load(['category', 'supplier']),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0'],
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => $product->fresh()->load(['category', 'supplier']),
        ]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}
