<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Ordering\Models\Client;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClientRequest;
use App\Http\Requests\Api\V1\UpdateClientRequest;
use App\Http\Resources\Api\V1\ClientResource;
use App\Http\Support\IndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Client::class, 'client');
    }

    public function index(Request $request): JsonResponse
    {
        $query = new IndexQuery(
            searchable: ['name', 'email'],
            sortable: ['name', 'email', 'created_at'],
            defaultSort: 'name',
        );

        return ClientResource::collection($query->paginate(Client::query(), $request))->response();
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        return ClientResource::make($client)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Client $client): JsonResponse
    {
        return ClientResource::make($client)->response();
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $client->update($request->validated());

        return ClientResource::make($client)->response();
    }

    public function destroy(Client $client): JsonResponse
    {
        if ($client->orders()->exists()) {
            return response()->json([
                'message' => 'This client cannot be deleted while orders reference it.',
            ], Response::HTTP_CONFLICT);
        }

        $client->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
