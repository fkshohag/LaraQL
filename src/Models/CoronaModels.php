<?php

namespace Shohag\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Shohag\Mixins\ModelMixins\QueryMixin;

/**
 * @author Fazlul Kabir Shohag <shohag.fks@gmail.com>
 */

class CoronaModels extends Model
{
    private $model;
    use QueryMixin;

    public $clientQueryFields = array();

    public function __construct(Model $model){
        $this->model = $model;
    }

    public function createResource($request) {
        $serializerConfig = $this->createSerializer();
        $direct_fields = $serializerConfig['direct_fields'];
        $query_result = array();

        foreach($direct_fields as $key => $field) {
            $query_result[$field['level']] = $field['model']::select($field['fields'])->get();
        }
        return isset($serializerConfig['other_fields']) && !empty($serializerConfig['other_fields']) ?
            array_merge($query_result, $serializerConfig['other_fields']) : $query_result;
    }

    function storeResource($request) {
        $EntityModel = $this->model;
        $fields = $EntityModel->postSerializerFields();

        if(method_exists($EntityModel, 'fieldsValidator')) {
            $validator = Validator::make($request->all(), $EntityModel->fieldsValidator());
            if ($validator->fails()) return response()->json(['errors' => $validator->messages()]);
        }
    
        $resource = new $EntityModel();
        foreach ($fields as $field) {
            if(isset($request->$field)) {
                $resource->$field = $request->$field;
            }
        }

        $resource->save();
        $resource = $EntityModel::find($resource->id);
        return $resource;
    }

    function updateResource($request, $id)
    {
        $EntityModel = $this->model;
        $fields = $EntityModel->postSerializerFields();
        $resource = $EntityModel::find($id);

        if (empty($resource)) return response()->json(['message' => 'Resource not found'], 404);

        if(method_exists($EntityModel, 'fieldsValidator')) {
            $validator = Validator::make($request->all(), $EntityModel->fieldsValidator());
            if ($validator->fails()) return response()->json(['errors' => $validator->messages()]);
        }
        foreach ($fields as $field) {
            if(isset($request->$field)) {
                $resource->$field = $request->$field;
            }
        }
        $resource->save();
        $resource = $EntityModel::find($resource->id);
        return $resource;
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    function deleteResource($id)
    {
        $resource = $this->model::find($id);
        if($resource) {
            $resource->delete();
            return true;
        }
        return false;
    }
}
