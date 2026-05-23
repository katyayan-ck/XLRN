<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Utils\EntityHistoryService;
use Illuminate\Http\Request;

class EntityHistoryController extends Controller
{
    public function getHistory($entityType, $entityId, EntityHistoryService $service)
    {
        $model = $this->resolveEntity($entityType, $entityId);
        return response()->json($service->getFullHistory($model));
    }

    public function addThread($entityType, $entityId, Request $request, EntityHistoryService $service)
    {
        $model = $this->resolveEntity($entityType, $entityId);
        $master = $service->createMaster($model);

        $thread = $service->addThread(
            $master,
            $request->input('action', 'commented'),
            $request->input('title'),
            $request->input('body'),
            $request->input('extra_data', []),
            null,
            auth()->user()
        );

        return response()->json($thread, 201);
    }

    private function resolveEntity($type, $id)
    {
        $modelClass = "App\\Models\\" . ucfirst($type);
        return $modelClass::findOrFail($id);
    }
}