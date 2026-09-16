<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        
        $perPage = min(
            max((int) $request->query('per_page', 10), 1),
            100
        );

        $products = Product::query()
            ->with(['category', 'supplier'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->query('category_id'));
            })
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $query->where('supplier_id', $request->query('supplier_id'));
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product->load(['category', 'supplier'])),
        ], 201);
    }

    public function show(Product $product)
    {
        return new ProductResource(
            $product->load(['category', 'supplier'])
        );
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product->fresh()->load(['category', 'supplier'])),
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
