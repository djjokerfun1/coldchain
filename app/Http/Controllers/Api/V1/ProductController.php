<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Ordering\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Support\IndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = new IndexQuery(
            filterable: ['storage_class'],
            searchable: ['sku', 'name'],
            sortable: ['sku', 'name', 'created_at'],
            defaultSort: 'name',
        );

        return ProductResource::collection($query->paginate(Product::query(), $request))->response();
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return ProductResource::make($product)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Product $product): JsonResponse
    {
        return ProductResource::make($product)->response();
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return ProductResource::make($product)->response();
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->orderLines()->exists()) {
            return response()->json([
                'message' => 'This product cannot be deleted while order lines reference it.',
            ], Response::HTTP_CONFLICT);
        }

        $product->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
