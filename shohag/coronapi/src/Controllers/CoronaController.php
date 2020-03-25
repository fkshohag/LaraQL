<?php

namespace Shohag\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Shohag\Collections\CoronaCollection;
use Shohag\Resources\CoronaResource;

/**
 * @author Fazlul Kabir Shohag <shohag.fks@gmail.com>
 */
class CoronaController extends Controller
{
    protected $limit = 10;
    protected $order_by = 'DESC';
    /**
     * @var EntityModel
     */
    protected $EntityInstance;
    protected $serializerFields = [];

    public function __construct()
    {
        /**
         * Serializer field set for every model individually.
         */
        $this->serializerFields = $this->EntityInstance->SerializerFields();
    }


    /**
     * Display a listing of the resource.
     *
     * @return CoronaCollection
     */
    public function index(Request $request)
    {
        $limit = $request->per_page ? $request->per_page : $this->limit;
        $order_by = $request->order_by ? $request->order_by: $this->order_by;
        
       if(($request->filters)) {
            $resource = $this->EntityInstance->getAll($request->filters, $limit, $order_by);
            $newCoronaCollection = new CoronaCollection($resource, $this->serializerFields);
        } else {
            $resource = $this->EntityInstance->getAll(null, $limit, $order_by);
            $newCoronaCollection = new CoronaCollection($resource, $this->serializerFields);
        }

        if(isset($request->queryFields) && !empty($request->queryFields)) {
            $queryFields = explode(',', $request->queryFields);
            $newCoronaCollection->SerializerFieldsSet($queryFields);
        }

        $newCoronaCollection->getEntityModelInstance($this->EntityInstance);
        return $newCoronaCollection;
    }

     /**
     * create a newly resource in storage.
     *
     * @param Request $request
     * @return CoronaResource|JsonResponse
     */
    public function create(Request $request) {
        $response = $this->EntityInstance->createResource($request);
        return response()->json(['data' => $response], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return CoronaResource|JsonResponse
     */
    public function store(Request $request)
    {
        $result = $this->EntityInstance->storeResource($request);
        return (is_object(json_decode($result))) === false ?  $result :  new CoronaResource($result, $this->serializerFields);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return CoronaResource|JsonResponse
     */
    public function show($id)
    {
        $result = $this->EntityInstance->getResourceById($id);
        if (empty($result)) return response()->json(['errors' => 'Resource not found'], 404);
        return (is_object(json_decode($result))) === false ?  $result :  new CoronaResource($result, $this->serializerFields);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int  $id
    * @return CoronaResource|JsonResponse
     */
    public function update(Request $request, $id)
    {
        $result = $this->EntityInstance->updateResource($request, $id);
        return (is_object(json_decode($result))) === false ?  $result :  new CoronaResource($result, $this->serializerFields);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return CoronaResource|JsonResponse
     */
    public function destroy($id)
    {
        $isDeleted = $this->EntityInstance->deleteResource($id);
        if($isDeleted) {
            return response()->json(['data' => 'Resource deleted successfully!'], 200);
        } else {
            return response()->json(['data' => 'Resource not found!'], 200); 
        }
    }
}
