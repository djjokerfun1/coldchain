<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Ordering\Enums\OrderStatus;
use App\Domain\Ordering\Models\Order;
use App\Domain\Ordering\ValueObjects\Address;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Requests\Api\V1\UpdateOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Support\IndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = new IndexQuery(
            filterable: ['client_id', 'status'],
            sortable: ['reference', 'placed_at', 'created_at'],
            defaultSort: 'created_at',
        );

        $orders = $query->paginate(Order::query()->with('client'), $request);

        return OrderResource::collection($orders)->response();
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = DB::transaction(function () use ($request): Order {
            $order = Order::create([
                'client_id' => $request->integer('client_id'),
                'reference' => $this->generateReference(),
                'status' => OrderStatus::Draft,
                'pickup_address' => Address::fromArray($request->array('pickup_address')),
                'delivery_address' => Address::fromArray($request->array('delivery_address')),
            ]);

            foreach ($request->array('lines') as $line) {
                $order->lines()->create([
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                ]);
            }

            return $order;
        });

        return OrderResource::make($order->load('client', 'lines.product'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Order $order): JsonResponse
    {
        return OrderResource::make($order->load('client', 'lines.product'))->response();
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('pickup_address', $data)) {
            $data['pickup_address'] = Address::fromArray($data['pickup_address']);
        }

        if (array_key_exists('delivery_address', $data)) {
            $data['delivery_address'] = Address::fromArray($data['delivery_address']);
        }

        $order->update($data);

        return OrderResource::make($order->load('client', 'lines.product'))->response();
    }

    public function destroy(Order $order): JsonResponse
    {
        if ($order->shipments()->exists()) {
            return response()->json([
                'message' => 'This order cannot be deleted while shipments reference it.',
            ], Response::HTTP_CONFLICT);
        }

        $order->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function generateReference(): string
    {
        return 'ORD-'.strtoupper(Str::random(6));
    }
}
