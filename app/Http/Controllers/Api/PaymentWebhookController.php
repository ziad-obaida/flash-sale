<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\PaymentWebhookRequest;
use App\Services\PaymentWebhookService;

class PaymentWebhookController extends BaseController
{
    protected $webhookService;

    public function __construct(PaymentWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function handle(PaymentWebhookRequest $request)
    {
        $result = $this->webhookService->handleWebhook($request->validated());

        return $this->sendResponse(true, $result['message'], $result['data'], $result['status']);
    }
}
