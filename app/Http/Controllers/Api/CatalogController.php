<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCatalogRequest;
use App\Http\Requests\UpdateCatalogRequest;
use App\Http\Resources\CatalogResource;
use App\Models\Catalog;
use App\Services\CatalogService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    protected $service;

    public function __construct(CatalogService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $catalogs = $this->service->list($request->all());
        return CatalogResource::collection($catalogs);
    }

    public function show(int $id)
    {
        $catalog = $this->service->find($id);
        return new CatalogResource($catalog);
    }

    public function store(StoreCatalogRequest $request)
    {
        $catalog = $this->service->store($request->validated());
        return (new CatalogResource($catalog))->response()->setStatusCode(201);
    }

    public function update(UpdateCatalogRequest $request, Catalog $catalog)
    {
        $updateCatalog = $this->service->update($catalog, $request->validated());
        return new CatalogResource($updateCatalog);
    }

    public function destroy(Catalog $catalog)
    {
        $this->service->destroy($catalog);

        return response()->json(['message' => 'Catalog deleted successfully']);
    }
}
