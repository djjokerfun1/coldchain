<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Shipping\Models\Vehicle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreVehicleRequest;
use App\Http\Requests\Api\V1\UpdateVehicleRequest;
use App\Http\Resources\Api\V1\VehicleResource;
use App\Http\Support\IndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VehicleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Vehicle::class, 'vehicle');
    }

    public function index(Request $request): JsonResponse
    {
        $query = new IndexQuery(
            searchable: ['registration'],
            sortable: ['registration', 'created_at'],
            defaultSort: 'registration',
        );

        return VehicleResource::collection($query->paginate(Vehicle::query()->with('driver'), $request))->response();
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return VehicleResource::make($vehicle->load('driver'))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        return VehicleResource::make($vehicle->load('driver'))->response();
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $vehicle->update($request->validated());

        return VehicleResource::make($vehicle->load('driver'))->response();
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
