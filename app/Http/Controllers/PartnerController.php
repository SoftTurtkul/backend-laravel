<?php

namespace App\Http\Controllers;

use App\Http\Requests\PartnerUpdateRequest;
use App\Http\Services\PartnerService;
use App\Http\Requests\PartnerRequest;
use App\Models\History;
use App\Models\Partner;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{

    /**
     * @var PartnerService
     */
    private PartnerService $service;

    public function __construct(PartnerService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return $this->service->all();
    }

    public function indexPublic()
    {
        return $this->service->publicAll();
    }

    public function store(PartnerRequest $request)
    {
        return $this->service->save($request);
    }

    public function show(Partner $partner)
    {
        return $this->success(['partner' => $partner]);
    }

    public function update(PartnerUpdateRequest $request, Partner $partner)
    {
        return $this->service->update($partner, $request);
    }

    public function updateImage(PartnerUpdateRequest $request, Partner $partner)
    {
        return $this->service->updateImage($partner, $request);
    }

    public function destroy(Partner $partner)
    {
        return $this->service->destroy($partner);
    }

    public function statPartner()
    {
        $today = now()->format('Y-m-d');  // Today's date in 'Y-m-d' format

        return $this->indexResponse(History::query()
            ->join('orders', 'histories.order_id', '=', 'orders.id')
            ->join('partners', 'orders.partner_id', '=', 'partners.id')
            ->select(
                'orders.partner_id',
                'partners.name as partner_name',
                DB::raw('COUNT(CASE WHEN DATE(histories.created_at) = '.$today.' THEN 1 END) AS daily_count'),
                DB::raw('COUNT(CASE WHEN YEAR(histories.created_at) = YEAR(CURDATE()) AND MONTH(histories.created_at) = MONTH(CURDATE()) THEN 1 END) AS monthly_count'),
                DB::raw('COUNT(CASE WHEN YEAR(histories.created_at) = YEAR(CURDATE()) THEN 1 END) AS yearly_count'),
                DB::raw('SUM(CASE WHEN DATE(histories.created_at) = '.$today.' THEN (orders.total_price - orders.delivery_price) ELSE 0 END) AS daily_sum'),
                DB::raw('SUM(CASE WHEN YEAR(histories.created_at) = YEAR(CURDATE()) AND MONTH(histories.created_at) = MONTH(CURDATE()) THEN (orders.total_price - orders.delivery_price) ELSE 0 END) AS monthly_sum'),
                DB::raw('SUM(CASE WHEN YEAR(histories.created_at) = YEAR(CURDATE()) THEN (orders.total_price - orders.delivery_price) ELSE 0 END) AS yearly_sum')
            )
            ->where('histories.status', 2)  // Filter by status = 2
            ->groupBy('orders.partner_id', 'partners.name')  // Group by partner_id and partner name
            ->paginate(\request()->get('limit', 20))
            ->toArray());
    }
}
