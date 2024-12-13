<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partner;

class OrderController extends Controller
{
    public function index(Partner $partner)
    {
        if (\request()->routeIs('orders.index')) {
            $query = Order::query();
            if (\request()->has('from') && \request()->has('to')) {
                $from = \request()->input('from');
                $to = \request()->input('to');
                $query = $query->whereRaw("date(created_at)>='{$from}' and date(created_at)<='{$to}'");
            }
            $query = $query->orderBy('created_at')
                ->with('items')
                ->with('customer')
                ->with('items.product')
                ->with('partner')
            ->get()
            ->toArray();

            return $this->success(['orders' => $query]);
        }
        return $this->success(['orders' => $partner->orders]);

    }

    public function getOrdersByStatus(Request $request, $customer = null)
    {
        if ($customer != null) return $this->success([
            'orders' => Order::query()->with('customer')
                ->with('partner')
                ->where('customer_id', $customer)
                ->whereIn('status', [-1, 4, 2])
                ->orderByDesc('id')->get()
        ]);

        return $this->success([
            'orders' => Order::with('customer')
                ->where('status', $request->status)
                ->orderByDesc('id')->get()
        ]);
    }

    public function getDeliveryOrders($customer)
    {
        return $this->success([
            'orders' => Order::query()->where('customer_id', $customer)
                ->with('customer')
                ->with('partner')
                ->with('driver')
                ->orderBy('id')
                ->whereIn('status', [0, 1, 2, 3, 10, 31])->get()
        ]);
    }

    public function getOrderItems($order)
    {
        return $this->success([
            'items' => OrderItem::query()
                ->whereHas('product')
                ->with('product')
                ->with('order')
                ->with('order.customer')
                ->with('order.partner')
                ->where('order_id', $order)->get()
        ]);
    }

    public function store(OrderRequest $request)
    {
        $data = $request->validated();
        $data['total_price'] += $data['delivery_price'];
        $order = Order::query()->create($data);
        foreach ($data['order_items'] as $item) {
            DB::table('order_items')->insert([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ]);
        }
        return $this->success([
            'order' => $order,
            'payme' => $data['payment_type'] == 0 ? "https://checkout.payme.uz/" . base64_encode("m=6708c357e64d929b0e41a59b;ac.order_id=" . $order->id . ";a=" . ($data['total_price'] * 100)) : ''
        ]);
    }

    public function show(Order $order)
    {
        return $this->success(['order' => $order]);
    }

    public function changeOrderStatus(Request $request, Order $order)
    {
        if ($request->status) {
            $history = new History();
            $history->order_id = $order->id;
            $history->status = $request->status;
            $history->save();
            $order->update(['status' => $request->status]);
            return $this->success($order);
        }

        return $this->fail([], 'Invalid status');
    }

    public function changeItemStatus(Request $request, OrderItem $item)
    {
        if ($request->status) {
            $item->update(['status' => $request->status]);
            return $this->success($item);
        }

        return $this->fail([], 'Invalid status');
    }
}
