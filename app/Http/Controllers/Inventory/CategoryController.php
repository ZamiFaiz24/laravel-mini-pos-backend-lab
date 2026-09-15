<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Category::query()
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => $category,
        ], 201);
    }

    public function show(Category $category)
    {
        return response()->json([
            'data' => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => $category->fresh(),
        ]);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
