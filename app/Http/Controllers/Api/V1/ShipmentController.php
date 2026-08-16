<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\Shipment;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreShipmentRequest;
use App\Http\Requests\Api\V1\UpdateShipmentRequest;
use App\Http\Resources\Api\V1\ShipmentResource;
use App\Http\Support\IndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Shipment::class, 'shipment');
    }

    public function index(Request $request): JsonResponse
    {
        $query = new IndexQuery(
            filterable: ['status'],
            sortable: ['reference', 'created_at'],
            defaultSort: 'created_at',
        );

        $shipments = Shipment::query()->with(['order', 'driver', 'vehicle']);

        // The policy gates whether a role can view a shipment at all; it
        // can't scope the list, so the same rule is applied here as a query.
        $user = $request->user();
        match ($user?->role) {
            UserRole::Driver => $shipments->where('driver_id', $user->driver_id),
            UserRole::Client => $shipments->whereHas('order', fn ($q) => $q->where('client_id', $user->client_id)),
            default => null,
        };

        return ShipmentResource::collection($query->paginate($shipments, $request))->response();
    }

    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $shipment = Shipment::create([
            'order_id' => $request->integer('order_id'),
            'driver_id' => $request->integer('driver_id') ?: null,
            'vehicle_id' => $request->integer('vehicle_id') ?: null,
            'reference' => $this->generateReference(),
            'status' => ShipmentStatus::Pending,
        ]);

        return ShipmentResource::make($shipment->load('order', 'driver', 'vehicle'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Shipment $shipment): JsonResponse
    {
        return ShipmentResource::make($shipment->load('order', 'driver', 'vehicle'))->response();
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): JsonResponse
    {
        $shipment->update($request->validated());

        return ShipmentResource::make($shipment->load('order', 'driver', 'vehicle'))->response();
    }

    public function destroy(Shipment $shipment): JsonResponse
    {
        if ($shipment->trackingEvents()->exists()) {
            return response()->json([
                'message' => 'This shipment cannot be deleted once it has tracking history.',
            ], Response::HTTP_CONFLICT);
        }

        $shipment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function generateReference(): string
    {
        return 'SHP-'.strtoupper(Str::random(6));
    }
}
