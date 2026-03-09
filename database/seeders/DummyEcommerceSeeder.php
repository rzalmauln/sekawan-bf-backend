<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Catalog;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;

class DummyEcommerceSeeder extends Seeder
{
    public function run(): void
    {
        $catalogs = Catalog::all();

        // ========================
        // Create Items
        // ========================

        $items = [];

        foreach ($catalogs as $catalog) {

            for ($i = 1; $i <= 3; $i++) {

                $item = Item::create([
                    'catalog_id' => $catalog->id,
                    'name' => $catalog->name . ' Bird ' . $i,
                    'slug' => Str::slug($catalog->name . '-bird-' . $i),
                    'description' => 'Dummy bird for testing',
                    'price' => rand(800000, 2500000),
                    'stock' => rand(1, 5),
                    'type' => 'jantan',
                    'is_active' => true
                ]);

                $items[] = $item;
            }
        }

        // ========================
        // Create Customers
        // ========================

        $customers = [];

        for ($i = 1; $i <= 10; $i++) {

            $customers[] = Customer::create([
                'name' => 'Customer ' . $i,
                'email' => "customer{$i}@mail.com",
                'phone' => '08123' . rand(1000000, 9999999),
                'address' => 'Dummy address',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'postal_code' => '12345'
            ]);
        }

        // ========================
        // Create Orders
        // ========================

        for ($i = 1; $i <= 25; $i++) {

            $customer = $customers[array_rand($customers)];

            $order = Order::create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'customer_id' => $customer->id,
                'total_price' => 0,
                'status' => collect([
                    'pending',
                    'paid',
                    'shipped',
                    'completed'
                ])->random(),
                'paid_at' => now()->subDays(rand(1, 30)),
                'shipped_at' => now()->subDays(rand(1, 30)),
                'completed_at' => now()->subDays(rand(1, 30))
            ]);

            $total = 0;

            $orderItemsCount = rand(1, 3);

            for ($j = 1; $j <= $orderItemsCount; $j++) {

                $item = $items[array_rand($items)];

                $qty = rand(1, 2);

                $subtotal = $item->price * $qty;

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'unit_price' => $item->price,
                    'qty' => $qty,
                    'subtotal' => $subtotal
                ]);

                $total += $subtotal;
            }

            $order->update([
                'total_price' => $total
            ]);
        }
    }
}
