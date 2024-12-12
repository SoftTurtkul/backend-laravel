<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeliveryRequest;
use App\Http\Services\DeliveryService;
use App\Models\Delivery;
use App\Models\History;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Requests\DriverOrderRequest;
use App\Http\Requests\DriverRequest;
use App\Http\Requests\HistoryRequest;
use App\Http\Requests\LocationRequest;
use App\Http\Services\DriverService;
use App\Models\Car;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    /**
     * @var DriverService
     */
    private DeliveryService $service;


    public function __construct(DeliveryService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return $this->service->get();
    }

    public function store(DeliveryRequest $request)
    {
        return $this->service->store($request->validated());
    }

    public function show($delivery)
    {
        $delivery = Delivery::query()->find($delivery);
        return $this->success(['delivery' => $delivery]);
    }

    public function getTariff($driver)
    {
        return Car::query()->where('driver_id', $driver)->firstOrFail()->tariff;
    }

    public function addSum(Request $request, Delivery $delivery)
    {
        return $this->service->addSum($request, $delivery);
    }

    public function update(Request $request, Delivery $delivery)
    {
        return $this->service->update($delivery, $this->validated($request));
    }

    public function updateLocation(Request $request, Delivery $delivery)
    {
        return $this->service->update(Delivery::query()->where(['id' => \auth('sanctum')->user()->id])->get()->first(),
            [
                'longitude' => $request->post('longitude'),
                'latitude' => $request->post('latitude'),
            ]
        );
    }

    public function destroy(Delivery $delivery)
    {
        return $this->service->destroy($delivery);
    }

    public function activate(Request $request, Delivery $delivery)
    {
        return $this->service->activate($request->get('status'), $delivery);
    }

    public function check(LocationRequest $request)
    {
        return $this->service->check($request);
    }

    public function mergeOrder(DriverOrderRequest $request)
    {
        return $this->service->mergeOrder($request);
    }

    public function history(HistoryRequest $request, Driver $driver)
    {
        return $this->service->writeHistory($request, $driver);
    }

    public function orders()
    {
        return $this->service->ordersInAir();
    }

    public function daily($driver)
    {
        return $this->service->daily($driver);
    }

    /* Full history of clients */
    public function clients($driver)
    {
        return $this->service->clients($driver);
    }

    public function profit($driver)
    {
        return $this->service->profit($driver);
    }

    public function getNewOrders()
    {
        return $this->success([
            'orders' => Order::query()
                ->where(['driver_id' => 0, 'status' => 1])
                ->with('partner')
                ->with('customer')
                ->orderBy('updated_at', 'desc')
                ->get()
        ]);
    }

    public function changeOrderStatus()
    {
        $status = \request()->post('status');
        if ($status == 3) {
            $orders = Order::query()
                ->where(['driver_id' => 0, 'status' => 1, 'id' => \request()->route('order')])
                ->exists();
        } elseif ($status == 31) {
            $orders = Order::query()
                ->where(['driver_id' => \auth('sanctum')->user()->id, 'status' => 2, 'id' => \request()->route('order')])
                ->exists();
        } elseif ($status == 4) {
            $orders = Order::query()
                ->where(['driver_id' => \auth('sanctum')->user()->id, 'status' => 31, 'id' => \request()->route('order')])
                ->exists();
        }
        if ($orders) {
            $order = Order::query()->where(['id' => \request()->route('order')])->first();
            $order->status = \request()->post('status');
            $order->driver_id = \auth('sanctum')->user()->id;
            $order->update();
            if ($status == 4 && $order->payment_type == 0) {
                $delivery_price = Order::query()
                    ->where(['id' => \request()->route('order')])->first()->delivery_price ?? 0;
                $delivery = Delivery::query()
                    ->where(['id' => \auth('sanctum')->user()->id])
                    ->first();
                $delivery->sum += $delivery_price;
                $delivery->update();
            }
            $history = new History();
            $history->driver_id = \auth('sanctum')->user()->id;
            $history->order_id = $order->id;
            $history->status = $status;
            $history->save();
            return $this->success([
            ]);
        }
        return $this->fail([
            'order not found'
        ]);

    }

    public function me()
    {
        $id = \auth('sanctum')->user()->id;
        $ordersCount=History::query()->where('driver_id', $id)->whereDay('created_at', '=', now()->day)->where('status',Order::STATE_FINISHED)->orderByDesc('created_at')->count();
        return $this->success(
            ['profile' => Delivery::query()
                ->where(['id' => $id])
                ->first(),
                'ordersCount' => $ordersCount
            ]
        );

    }

    public function current()
    {
        $order = Order::query()
            ->with('customer')
            ->with('partner')
            ->with('items')
            ->with('items.product')
            ->where(['driver_id' => \auth('sanctum')->user()->id])
            ->whereIn('status', [2, 3, 31])
            ->first();
        return $this->success($order);
    }
    public function statDelivery(){
        $today = now()->format('Y-m-d');  // Today's date in 'Y-m-d' format
        return $this->indexResponse(History::query()
             ->join('orders', 'histories.order_id', '=', 'orders.id')
            ->join('delivery', 'histories.driver_id', '=', 'delivery.id')
            ->select(
                'histories.driver_id',
                'delivery.name as driver_name',
                DB::raw('COUNT(CASE WHEN DATE(histories.created_at) = '.$today.' THEN 1 END) AS daily_count'),
                DB::raw('COUNT(CASE WHEN YEAR(histories.created_at) = YEAR(CURDATE()) AND MONTH(histories.created_at) = MONTH(CURDATE()) THEN 1 END) AS monthly_count'),
                DB::raw('COUNT(CASE WHEN YEAR(histories.created_at) = YEAR(CURDATE()) THEN 1 END) AS yearly_count'),
                DB::raw('SUM(CASE WHEN DATE(histories.created_at) = '.$today.' THEN orders.delivery_price ELSE 0 END) AS daily_sum'),
                DB::raw('SUM(CASE WHEN YEAR(histories.created_at) = YEAR(CURDATE()) AND MONTH(histories.created_at) = MONTH(CURDATE()) THEN orders.delivery_price ELSE 0 END) AS monthly_sum'),
                DB::raw('SUM(CASE WHEN YEAR(histories.created_at) = YEAR(CURDATE()) THEN orders.delivery_price ELSE 0 END) AS yearly_sum')
            )
            ->where(['histories.status'=>31])  // Filter by status 4 or 31
            ->groupBy('histories.driver_id', 'delivery.name')
            ->paginate(\request()->get('limit', 20))
            ->toArray());


    }
    public function statDeliveryOverall(){
        $today = now()->format('Y-m-d');  // Today's date in 'Y-m-d' format
        return $this->indexResponse(History::query()
            ->join('orders', 'histories.order_id', '=', 'orders.id')
            ->join('delivery', 'histories.driver_id', '=', 'delivery.id')
            ->select(
                DB::raw('COUNT(CASE WHEN DATE(histories.created_at) = '.$today.' THEN 1 END) AS daily_count'),
                DB::raw('COUNT(CASE WHEN YEAR(histories.created_at) = YEAR(CURDATE()) AND MONTH(histories.created_at) = MONTH(CURDATE()) THEN 1 END) AS monthly_count'),
                DB::raw('COUNT(CASE WHEN YEAR(histories.created_at) = YEAR(CURDATE()) THEN 1 END) AS yearly_count'),
                DB::raw('SUM(CASE WHEN DATE(histories.created_at) = '.$today.' THEN orders.delivery_price ELSE 0 END) AS daily_sum'),
                DB::raw('SUM(CASE WHEN YEAR(histories.created_at) = YEAR(CURDATE()) AND MONTH(histories.created_at) = MONTH(CURDATE()) THEN orders.delivery_price ELSE 0 END) AS monthly_sum'),
                DB::raw('SUM(CASE WHEN YEAR(histories.created_at) = YEAR(CURDATE()) THEN orders.delivery_price ELSE 0 END) AS yearly_sum')
            )
            ->where(['histories.status'=>31])
            ->get()// Filter by status 4 or 31
            ->toArray());
    }
//    public function offer() {
//        return Storage::get(public_path('taxi.pdf'));
//    }
}
