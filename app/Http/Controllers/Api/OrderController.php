<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use App\Models\Hold;

class OrderController extends BaseController
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function store(StoreOrderRequest $request)
    {
        $hold = Hold::find($request->hold_id);

        $result = $this->orderService->createOrder($hold, $request->customer_info);

        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['status']);
        }

        return $this->sendResponse(true, 'Order created successfully', [
            'order_id' => $result->id,
        ]);
    }
}
