<?php

namespace App\Http\Controllers;

use App\Http\Requests\PartnerUpdateRequest;
use App\Http\Services\PartnerService;
use App\Http\Requests\PartnerRequest;
use App\Models\Partner;

class PartnerController extends Controller {

    /**
     * @var PartnerService
     */
    private PartnerService $service;

    public function __construct(PartnerService $service) {
        $this->service = $service;
    }

    public function index() {
        return $this->service->all();
    }
    public function indexPublic() {
        return $this->service->publicAll();
    }

    public function store(PartnerRequest $request) {
        return $this->service->save($request);
    }

    public function show(Partner $partner) {
        return $this->success(['partner' => $partner]);
    }

    public function update(PartnerUpdateRequest $request, Partner $partner) {
        return $this->service->update($partner, $request->validated());
    }

    public function destroy(Partner $partner) {
        return $this->service->destroy($partner);
    }
}
