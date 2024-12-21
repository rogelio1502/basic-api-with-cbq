<?php

namespace App\Domains\Public\Product\Queries;

use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Uxmal\Backend\Attributes\RegisterQuery;

#[RegisterQuery('/public/products', name: 'qry.public.products.v1')]
class Index
{
    /**
     * @throws Exception
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(Product::all());
    }
}
