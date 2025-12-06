<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\ShowProductRequest;
use App\Services\ProductService;

class ProductController extends BaseController
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function show(ShowProductRequest $request, $id)
    {
        $result = $this->productService->fetchProductWithStock((int)$id);

        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['status']);
        }

        return $this->sendResponse(true, 'Product fetched successfully', $result);
    }
}
