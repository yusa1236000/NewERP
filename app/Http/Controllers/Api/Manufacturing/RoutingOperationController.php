<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Manufacturing\Routing;
use App\Models\Manufacturing\RoutingOperation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RoutingOperationController extends Controller
{
    /**
     * Display a listing of the resource for a specific routing.
     *
     * @param  int  $routingId
     * @return \Illuminate\Http\Response
     */
    public function index($routingId)
    {
        $routing = Routing::find($routingId);

        if (!$routing) {
            return response()->json(['message' => 'Routing not found'], 404);
        }

        $operations = RoutingOperation::with(['workCenter', 'unitOfMeasure'])
            ->where('routing_id', $routingId)
            ->orderBy('sequence')
            ->get();

        return response()->json(['data' => $operations]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $routingId
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $routingId)
    {
        Log::info('RoutingOperationController::store - Request received', [
            'routing_id' => $routingId,
            'request_data' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        $routing = Routing::find($routingId);

        if (!$routing) {
            Log::error('RoutingOperationController::store - Routing not found', ['routing_id' => $routingId]);
            return response()->json(['message' => 'Routing not found'], 404);
        }

        Log::info('RoutingOperationController::store - Routing found', [
            'routing_id' => $routingId,
            'routing_data' => $routing->toArray()
        ]);

        $validator = Validator::make($request->all(), [
            'workcenter_id' => 'required|integer|exists:work_centers,workcenter_id',
            'operation_name' => 'required|string|max:100',
            'work_flow' => 'nullable|string|max:100',
            'models' => 'nullable|string|max:100',
            'sequence' => 'required|integer',
            'setup_time' => 'required|numeric',
            'run_time' => 'required|numeric',
            'uom_id' => 'required|integer|exists:unit_of_measures,uom_id',
            'labor_cost' => 'required|numeric',
            'overhead_cost' => 'required|numeric',
            'dimensi' => 'nullable|string|max:100',
            'toleransi_max' => 'nullable|string|max:100',
            'toleransi_min' => 'nullable|string|max:100',
            'yield1' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            Log::error('RoutingOperationController::store - Validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Log::info('RoutingOperationController::store - Validation passed', [
            'request_data' => $request->all()
        ]);

        $operation = new RoutingOperation();
        $operation->routing_id = $routingId;
        $operation->workcenter_id = $request->workcenter_id;
        $operation->operation_name = $request->operation_name;
        $operation->work_flow = $request->work_flow;
        $operation->models = $request->models;
        $operation->dimensi = $request->dimensi;
        $operation->toleransi_max = $request->toleransi_max;
        $operation->toleransi_min = $request->toleransi_min;
        $operation->sequence = $request->sequence;
        $operation->setup_time = $request->setup_time;
        $operation->run_time = $request->run_time;
        $operation->uom_id = $request->uom_id;
        $operation->labor_cost = $request->labor_cost;
        $operation->overhead_cost = $request->overhead_cost;
        $operation->yield1 = $request->yield1;

        Log::info('RoutingOperationController::store - About to save operation', [
            'operation_data' => $operation->toArray()
        ]);

        try {
            $saved = $operation->save();
            Log::info('RoutingOperationController::store - Save result', [
                'saved' => $saved,
                'operation_id' => $operation->operation_id,
                'operation_data' => $operation->toArray()
            ]);

            return response()->json([
                'data' => $operation->load(['workCenter', 'unitOfMeasure']),
                'message' => 'Operation created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('RoutingOperationController::store - Save failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Failed to save operation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $routingId
     * @param  int  $operationId
     * @return \Illuminate\Http\Response
     */
    public function show($routingId, $operationId)
    {
        $routing = Routing::find($routingId);

        if (!$routing) {
            return response()->json(['message' => 'Routing not found'], 404);
        }

        $operation = RoutingOperation::with(['workCenter', 'unitOfMeasure'])
            ->where('routing_id', $routingId)
            ->where('operation_id', $operationId)
            ->first();

        if (!$operation) {
            return response()->json(['message' => 'Operation not found'], 404);
        }

        return response()->json(['data' => $operation]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $routingId
     * @param  int  $operationId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $routingId, $operationId)
    {
        $routing = Routing::find($routingId);

        if (!$routing) {
            return response()->json(['message' => 'Routing not found'], 404);
        }

        $operation = RoutingOperation::where('routing_id', $routingId)
            ->where('operation_id', $operationId)
            ->first();

        if (!$operation) {
            return response()->json(['message' => 'Operation not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'workcenter_id' => 'required|integer|exists:work_centers,workcenter_id',
            'operation_name' => 'required|string|max:100',
            'work_flow' => 'nullable|string|max:100',
            'models' => 'nullable|string|max:100',
            'sequence' => 'required|integer',
            'setup_time' => 'required|numeric',
            'run_time' => 'required|numeric',
            'uom_id' => 'required|integer|exists:unit_of_measures,uom_id',
            'labor_cost' => 'required|numeric',
            'overhead_cost' => 'required|numeric',
            'dimensi' => 'nullable|string|max:100',
            'toleransi_max' => 'nullable|string|max:100',
            'toleransi_min' => 'nullable|string|max:100',
            'yield1' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $operation->workcenter_id = $request->workcenter_id;
        $operation->operation_name = $request->operation_name;
        $operation->work_flow = $request->work_flow;
        $operation->models = $request->models;
        $operation->dimensi = $request->dimensi;
        $operation->toleransi_max = $request->toleransi_max;
        $operation->toleransi_min = $request->toleransi_min;
        $operation->sequence = $request->sequence;
        $operation->setup_time = $request->setup_time;
        $operation->run_time = $request->run_time;
        $operation->uom_id = $request->uom_id;
        $operation->labor_cost = $request->labor_cost;
        $operation->overhead_cost = $request->overhead_cost;
        $operation->yield1 = $request->yield1;
        $operation->save();

        return response()->json([
            'data' => $operation->load(['workCenter', 'unitOfMeasure']),
            'message' => 'Operation updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $routingId
     * @param  int  $operationId
     * @return \Illuminate\Http\Response
     */
    public function destroy($routingId, $operationId)
    {
        $routing = Routing::find($routingId);

        if (!$routing) {
            return response()->json(['message' => 'Routing not found'], 404);
        }

        $operation = RoutingOperation::where('routing_id', $routingId)
            ->where('operation_id', $operationId)
            ->first();

        if (!$operation) {
            return response()->json(['message' => 'Operation not found'], 404);
        }

        $operation->delete();

        return response()->json(['message' => 'Operation deleted successfully']);
    }
}
