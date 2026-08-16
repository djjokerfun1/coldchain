<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Shipping\Models\Driver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDriverRequest;
use App\Http\Requests\Api\V1\UpdateDriverRequest;
use App\Http\Resources\Api\V1\DriverResource;
use App\Http\Support\IndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DriverController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Driver::class, 'driver');
    }

    public function index(Request $request): JsonResponse
    {
        $query = new IndexQuery(
            searchable: ['name', 'license_number'],
            sortable: ['name', 'created_at'],
            defaultSort: 'name',
        );

        return DriverResource::collection($query->paginate(Driver::query(), $request))->response();
    }

    public function store(StoreDriverRequest $request): JsonResponse
    {
        $driver = Driver::create($request->validated());

        return DriverResource::make($driver)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Driver $driver): JsonResponse
    {
        return DriverResource::make($driver)->response();
    }

    public function update(UpdateDriverRequest $request, Driver $driver): JsonResponse
    {
        $driver->update($request->validated());

        return DriverResource::make($driver)->response();
    }

    public function destroy(Driver $driver): JsonResponse
    {
        $driver->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
