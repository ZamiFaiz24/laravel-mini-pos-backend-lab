<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Supplier::query()
                ->withCount('products')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_info' => [
                'required',
                'string',
                'max:255',
                'unique:suppliers,contact_info',
            ],
        ]);

        $supplier = Supplier::create($validated);

        return response()->json([
            'message' => 'Supplier created successfully.',
            'data' => $supplier,
        ], 201);
    }

    public function show(Supplier $supplier)
    {
        return response()->json([
            'data' => $supplier->load('products'),
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_info' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers', 'contact_info')
                    ->ignore($supplier->id),
            ],
        ]);

        $supplier->update($validated);

        return response()->json([
            'message' => 'Supplier updated successfully.',
            'data' => $supplier->fresh(),
        ]);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return response()->json([
            'message' => 'Supplier deleted successfully.',
        ]);
    }
}
