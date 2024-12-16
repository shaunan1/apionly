<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStoreRequest;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // GET /api/orders
    public function index(Request $request)
    {
        $ordersQuery = Order::query();
    
        // Filter berdasarkan tanggal
        if ($request->start_date) {
            $ordersQuery->where('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $ordersQuery->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }
    
        // Ambil semua data sebelum paginasi untuk menghitung total
        $ordersCollection = $ordersQuery->with(['items', 'payments', 'customer'])->get();
    
        // Hitung total dan jumlah yang diterima
        $total = $ordersCollection->sum(fn($order) => $order->total());
        $receivedAmount = $ordersCollection->sum(fn($order) => $order->receivedAmount());
    
        // Lakukan paginasi setelah menghitung total
        $ordersPaginated = $ordersQuery->latest()->paginate(10);
    
        return response()->json([
            'orders' => $ordersPaginated,
            'total' => $total,
            'received_amount' => $receivedAmount,
        ]);
    }
    

    // POST /api/orders
    public function store(OrderStoreRequest $request)
    {
        $order = Order::create([
            'customer_id' => $request->customer_id,
            'user_id' => $request->user()->id,
        ]);

        // Tambahkan item ke pesanan
        $cart = $request->user()->cart()->get();
        foreach ($cart as $item) {
            $order->items()->create([
                'price' => $item->price * $item->pivot->quantity,
                'quantity' => $item->pivot->quantity,
                'product_id' => $item->id,
            ]);

            // Kurangi stok produk
            $item->quantity -= $item->pivot->quantity;
            $item->save();
        }

        // Kosongkan keranjang pengguna
        $request->user()->cart()->detach();

        // Tambahkan pembayaran ke pesanan
        $order->payments()->create([
            'amount' => $request->amount,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Order successfully created.',
            'order' => $order->load(['items', 'payments', 'customer']),
        ]);
    }
}
