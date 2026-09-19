<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Services\Sales\SaleService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class SaleController extends Controller
{
    public function store(
        StoreSaleRequest $request,
        SaleService $saleService
    ): JsonResponse {
        try {
            $sale = $saleService->createSale(
                $request->user(),
                $request->validated()['items']
            );

            return response()->json([
                'message' => 'Sale created successfully.',
                'data' => new SaleResource($sale),
            ], 201);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
