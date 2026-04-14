<?php

namespace Database\Seeders;

use App\Models\Catalog;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyEcommerceSeeder extends Seeder
{
    public function run(): void
    {
        $catalogs = Catalog::all();

        $items = [];

        foreach ($catalogs as $catalog) {
            for ($i = 1; $i <= 3; $i++) {
                $slug = Str::slug($catalog->name . '-bird-' . $i);

                $item = Item::create([
                    'catalog_id' => $catalog->id,
                    'name' => $catalog->name . ' Bird ' . $i,
                    'slug' => $slug,
                    'description' => 'Dummy bird for testing',
                    'price' => rand(800000, 2500000),
                    'stock' => rand(1, 5),
                    'type' => 'jantan',
                    'certificate_path' => 'certificates/certificate-' . $slug . '.png',
                    'certificate_password' => strtoupper(Str::random(8)),
                    'image_path' => 'items/images/' . $slug . '.jpg',
                    'video_path' => 'items/videos/' . $slug . '.mp4',
                    'gaya_main' => collect(['Nagen', 'Teler', 'Fighter'])->random(),
                    'body' => collect(['Besar', 'Sedang', 'Kecil'])->random(),
                    'umur' => collect([1, 3, 6, 12, 18, 24])->random(),
                    'materi' => collect(['Isian rapat', 'Tembakan', 'Campuran'])->random(),
                    'volume' => collect(['Keras', 'Sedang', 'Tipis'])->random(),
                    'panjang_ekor' => collect(['Panjang', 'Sedang', 'Pendek'])->random(),
                    'warna' => collect(['Hitam', 'Coklat', 'Abu-abu'])->random(),
                    'warna_kaki' => collect(['Hitam', 'Merah muda', 'Abu-abu'])->random(),
                    'paruh' => collect(['Tebal', 'Sedang', 'Tipis'])->random(),
                    'jenis_kepala' => collect(['Kotak', 'Bulat', 'Panjang'])->random(),
                    'voer' => collect(['Voer A', 'Voer B', 'Voer C'])->random(),
                    'extra_fooding' => collect(['Jangkrik', 'Kroto', 'Ulat hongkong'])->random(),
                    'embun' => collect(['Ya', 'Tidak'])->random(),
                    'jemur' => collect(['15 menit', '30 menit', '45 menit'])->random(),
                    'mandi' => collect(['Pagi', 'Sore', 'Pagi dan sore'])->random(),
                    'tenggar' => collect(['1 kali', '2 kali', '3 kali'])->random(),
                    'krodong_ablak' => collect(['Ya', 'Tidak'])->random(),
                    'is_active' => true,
                ]);

                $items[] = $item;
            }
        }

        $customers = [];

        for ($i = 1; $i <= 10; $i++) {
            $customers[] = Customer::create([
                'name' => 'Customer ' . $i,
                'email' => "customer{$i}@mail.com",
                'phone' => '08123' . rand(1000000, 9999999),
                'address' => 'Dummy address',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'postal_code' => '12345',
            ]);
        }

        $statuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PAID,
            Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];

        for ($i = 1; $i <= 25; $i++) {
            $customer = $customers[array_rand($customers)];
            $status = $statuses[array_rand($statuses)];

            $order = Order::create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'customer_id' => $customer->id,
                'total_price' => 0,
                'status' => $status,
                'payment_proof_path' => null,
                'paid_at' => null,
                'shipped_at' => null,
                'completed_at' => null,
                'cancelled_at' => null,
            ]);

            $subtotal = 0;
            $orderItemsCount = rand(1, 3);

            for ($j = 1; $j <= $orderItemsCount; $j++) {
                $item = $items[array_rand($items)];
                $qty = rand(1, 2);
                $lineSubtotal = $item->price * $qty;

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'unit_price' => $item->price,
                    'qty' => $qty,
                    'subtotal' => $lineSubtotal,
                ]);

                $subtotal += $lineSubtotal;
            }
            $paidAt = in_array($status, [Order::STATUS_PAID, Order::STATUS_SHIPPED, Order::STATUS_COMPLETED], true)
                ? now()->subDays(rand(1, 15))
                : null;
            $shippedAt = in_array($status, [Order::STATUS_SHIPPED, Order::STATUS_COMPLETED], true)
                ? now()->subDays(rand(1, 10))
                : null;
            $completedAt = $status === Order::STATUS_COMPLETED
                ? now()->subDays(rand(1, 7))
                : null;
            $cancelledAt = $status === Order::STATUS_CANCELLED
                ? now()->subDays(rand(1, 7))
                : null;

            $order->update([
                'total_price' => $subtotal + ($shippingCost ?? 0),
                'payment_proof_path' => in_array($status, [Order::STATUS_PAID, Order::STATUS_SHIPPED, Order::STATUS_COMPLETED], true)
                    ? 'payment_proofs/proof-' . Str::lower(Str::random(10)) . '.jpg'
                    : null,
                'paid_at' => $paidAt,
                'shipped_at' => $shippedAt,
                'completed_at' => $completedAt,
                'cancelled_at' => $cancelledAt,
            ]);
        }
    }
}
