<?php

namespace App\Services\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleService
{
    public function createSale(User $cashier, array $items): Sale
    {
        if ($items === []) {
            throw new RuntimeException(
                'Sale must contain at least one item.'
            );
        }

        return DB::transaction(function () use ($cashier, $items) {
            $sale = Sale::create([
                'user_id' => $cashier->id,
                'total_amount' => 0,
                'status' => 'completed',
            ]);

            $totalCents = 0;

            foreach ($items as $item) {
                $product = Product::query()
                    ->lockForUpdate()
                    ->findOrFail($item['product_id']);

                $quantity = (int) $item['quantity'];

                if ($quantity < 1) {
                    throw new RuntimeException(
                        'Quantity must be greater than zero.'
                    );
                }

                if ($product->stock < $quantity) {
                    throw new RuntimeException(
                        "Insufficient stock for product: {$product->name}."
                    );
                }

                $priceCents = $this->toCents($product->price);
                $subtotalCents = $priceCents * $quantity;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price_at_sale' => $this->fromCents($priceCents),
                    'subtotal' => $this->fromCents($subtotalCents),
                ]);

                $product->decrement('stock', $quantity);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'reason' => "Sale #{$sale->id}",
                    'created_by' => $cashier->id,
                ]);

                $totalCents += $subtotalCents;
            }

            $sale->update([
                'total_amount' => $this->fromCents($totalCents),
            ]);

            return $sale->load('items.product', 'user');
        });
    }

    private function toCents(string|int|float $amount): int
    {
        $amount = number_format((float) $amount, 2, '.', '');
        [$whole, $fraction] = explode('.', $amount);

        return ((int) $whole * 100) + (int) $fraction;
    }

    private function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
