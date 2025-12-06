<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StoreHoldRequest;
use App\Services\HoldService;

class HoldController extends BaseController
{
    protected $holdService;

    public function __construct(HoldService $holdService)
    {
        $this->holdService = $holdService;
    }

    public function store(StoreHoldRequest $request)
    {
        $result = $this->holdService->createHold($request->product_id, $request->qty);

        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['status']);
        }

        return $this->sendResponse(true, 'Hold created successfully', $result, 201);
    }
}
