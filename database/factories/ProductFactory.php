<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    private const PRODUCTS_BY_CATEGORY = [
        'Makanan' => [
            'Beras Ramos 5 Kg' => [65000, 85000],
            'Mie Instan Goreng' => [2500, 4000],
            'Mie Instan Kuah' => [2500, 4000],
            'Biskuit Cokelat' => [5000, 18000],
            'Roti Tawar' => [12000, 20000],
        ],
        'Minuman' => [
            'Susu UHT 500 ML' => [9000, 16000],
            'Air Mineral 600 Ml' => [2500, 5000],
            'Teh Celup 25 Sachet' => [8000, 15000],
            'Kopi Bubuk 200 Gram' => [12000, 25000],
            'Jus Buah 1 Liter' => [18000, 35000],
        ],
        'Sembako' => [
            'Gula Pasir 1 Kg' => [16000, 22000],
            'Minyak Goreng 1 Liter' => [17000, 25000],
            'Tepung Terigu 1 Kg' => [12000, 18000],
            'Garam Dapur 200 Gram' => [3000, 7000],
        ],
        'Kebersihan' => [
            'Deterjen Bubuk 1 Kg' => [18000, 30000],
            'Pembersih Lantai 800 Ml' => [12000, 22000],
            'Tisu Wajah 250 Lembar' => [10000, 20000],
            'Sabun Cuci Piring 500 Ml' => [9000, 18000],
        ],
        'Perawatan Diri' => [
            'Sabun Mandi' => [3000, 15000],
            'Sampo 170 Ml' => [25000, 40000],
            'Pasta Gigi 120 Gram' => [8000, 15000],
            'Minyak Rambut 60 Ml' => [15000, 30000],
            'Minyak Wangi 50 Ml' => [20000, 45000],
        ],
        'Produk Bayi' => [
            'Popok Bayi Size M 20 Pcs' => [20000, 40000],
            'Minyak Telon 100 Ml' => [15000, 30000],
            'Sabun Bayi 100 ML' => [8000, 20000],
            'Tisu Basah Bayi 50 Lembar' => [5000, 15000],
        ],
        'Bumbu Dapur' => [
            'Kecap Manis 600 Ml' => [15000, 25000],
            'Saus Sambal 135 Ml' => [12000, 20000],
            'Kaldu Ayam 100 Gram' => [10000, 18000],
            'Merica Bubuk 50 Gram' => [5000, 10000],
        ],
        'Makanan Ringan' => [
            'Keripik Kentang' => [5000, 10000],
            'Kerupuk Udang' => [8000, 15000],
            'Kacang Panggang' => [6000, 12000],
            'Wafer Cokelat' => [10000, 20000],
        ],
    ];

    private static ?array $availableProducts = null;

    public function definition(): array
    {
        if (self::$availableProducts === null) {
            self::$availableProducts = collect(self::PRODUCTS_BY_CATEGORY)
                ->flatMap(function (array $products, string $categoryName) {
                    return collect($products)
                        ->map(fn(array $priceRange, string $productName) => [
                            'category_name' => $categoryName,
                            'name' => $productName,
                            'minimum_price' => $priceRange[0],
                            'maximum_price' => $priceRange[1],
                        ]);
                })
                ->values()
                ->all();
        }

        if (self::$availableProducts === []) {
            throw new \RuntimeException(
                'Tidak ada nama produk unik yang tersisa.'
            );
        }

        $index = fake()->numberBetween(
            0,
            count(self::$availableProducts) - 1
        );

        $selectedProduct = array_splice(
            self::$availableProducts,
            $index,
            1
        )[0];

        $category = Category::query()
            ->where('name', $selectedProduct['category_name'])
            ->firstOrFail();

        return [
            'category_id' => $category->id,
            'supplier_id' => Supplier::factory(),
            'sku' => fake()->unique()->bothify('SKU-####??'),
            'name' => $selectedProduct['name'],
            'price' => fake()->randomFloat(
                2,
                $selectedProduct['minimum_price'],
                $selectedProduct['maximum_price']
            ),
            'stock' => fake()->numberBetween(5, 100),
        ];
    }
}
