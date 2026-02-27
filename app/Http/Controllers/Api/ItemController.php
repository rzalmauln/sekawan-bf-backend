<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\ItemServices;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    protected $service;

    public function __construct(ItemServices $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $items = $this->service->list($request->all());
        return ItemResource::collection($items);
    }

    public function show(int $id)
    {
        $item = $this->service->find($id);
        return new ItemResource($item);
    }

    public function store(StoreItemRequest $request)
    {
        $item = $this->service->store($request->all());
        return (new ItemResource($item))->response()->setStatusCode(201);
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $updateItem = $this->service->update($item, $request->all());
        return new ItemResource($updateItem);
    }

    public function destroy(Item $item){
        $this->service->destroy($item);
        return response()->json(['message' => 'Catalog deleted successfully']);
    }

}
