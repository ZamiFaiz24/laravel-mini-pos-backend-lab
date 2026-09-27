<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Sales\SaleService;

test('sale reduces stock and records its details', function () {
    $cashier = User::factory()->create([
        'role' => 'cashier',
    ]);

    $category = Category::factory()->create([
        'name' => 'Makanan',
    ]);

    $supplier = Supplier::factory()->create();

    $product = Product::create([
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'sku' => 'TEST-SKU-001',
        'name' => 'Beras Test',
        'price' => 75000,
        'stock' => 10,
    ]);

    $sale = app(SaleService::class)->createSale($cashier, [
        [
            'product_id' => $product->id,
            'quantity' => 3,
        ],
    ]);

    expect($sale->user_id)->toBe($cashier->id)
        ->and((float) $sale->total_amount)->toBe(225000.0);

    expect($product->fresh()->stock)->toBe(7);

    $this->assertDatabaseHas('sale_items', [
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'price_at_sale' => 75000,
        'subtotal' => 225000,
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => 'out',
        'quantity' => 3,
        'created_by' => $cashier->id,
    ]);
});

test('sale rolls back all changes when one item has insufficient stock', function () {
    $cashier = User::factory()->create([
        'role' => 'cashier',
    ]);

    $category = Category::factory()->create([
        'name' => 'Makanan',
    ]);

    $supplier = Supplier::factory()->create();

    $firstProduct = Product::create([
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'sku' => 'TEST-SKU-002',
        'name' => 'Produk Pertama',
        'price' => 10000,
        'stock' => 10,
    ]);

    $secondProduct = Product::create([
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'sku' => 'TEST-SKU-003',
        'name' => 'Produk Kedua',
        'price' => 20000,
        'stock' => 2,
    ]);

    expect(fn() => app(SaleService::class)->createSale($cashier, [
        [
            'product_id' => $firstProduct->id,
            'quantity' => 1,
        ],
        [
            'product_id' => $secondProduct->id,
            'quantity' => 3,
        ],
    ]))->toThrow(
        RuntimeException::class,
        'Insufficient stock for product: Produk Kedua.'
    );

    expect(Sale::count())->toBe(0)
        ->and(SaleItem::count())->toBe(0)
        ->and(StockMovement::count())->toBe(0)
        ->and($firstProduct->fresh()->stock)->toBe(10)
        ->and($secondProduct->fresh()->stock)->toBe(2);
});

test('empty sale is rejected', function () {
    $cashier = User::factory()->create([
        'role' => 'cashier',
    ]);

    expect(fn() => app(SaleService::class)->createSale($cashier, []))
        ->toThrow(
            RuntimeException::class,
            'Sale must contain at least one item.'
        );

    expect(Sale::count())->toBe(0);
});

test('zero quantity is rejected', function () {
    $cashier = User::factory()->create([
        'role' => 'cashier',
    ]);

    $category = Category::factory()->create([
        'name' => 'Makanan',
    ]);

    $supplier = Supplier::factory()->create();

    $product = Product::create([
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
        'sku' => 'TEST-SKU-004',
        'name' => 'Produk Quantity Test',
        'price' => 10000,
        'stock' => 10,
    ]);

    expect(fn() => app(SaleService::class)->createSale($cashier, [
        [
            'product_id' => $product->id,
            'quantity' => 0,
        ],
    ]))->toThrow(
        RuntimeException::class,
        'Quantity must be greater than zero.'
    );

    expect(Sale::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(10);
});

test('cashier cannot create a category through the API', function () {
    $cashier = User::factory()->create([
        'role' => 'cashier',
    ]);

    $response = $this->actingAs($cashier, 'sanctum')
        ->postJson('/api/categories', [
            'name' => 'Kategori Cashier',
        ]);

    $response
        ->assertStatus(403)
        ->assertJson([
            'message' => 'Only administrators can perform this action.',
        ]);

    $this->assertDatabaseMissing('categories', [
        'name' => 'Kategori Cashier',
    ]);
});

test('admin can create a category through the API', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->postJson('/api/categories', [
            'name' => 'Kategori Admin',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Kategori Admin');

    $this->assertDatabaseHas('categories', [
        'name' => 'Kategori Admin',
    ]);
});
