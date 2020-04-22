<?php

namespace Shohag\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Shohag\Mixins\ModelMixins\QueryMixin;
use Shohag\Utilitys\ModelUtility\QueryUtility;

/**
 * @author Fazlul Kabir Shohag <shohag.fks@gmail.com>
 */

class LaraQLModel extends Model
{
    use QueryMixin, QueryUtility;
    private $model;
    protected $tableName;

    public $clientQueryFields = array();

    public function __construct(Model $model){
        $this->model = $model;
        $this->tableName = $this->getTable();
    }

        /**
     * @param int $resourcePerPage
     * @param string $orderBy
     * @return JsonResponse
     * @throws \ReflectionException
     */
    function getAll($filter = null, $resourcePerPage = 10, $orderBy = 'DESC')
    {
        $querySet = $this->model::orderBy($this->tableName.'.id', $orderBy);
        if($filter) {
            $querySet = $this->appliedMullipleFilter($querySet, $filter);
        }
        return $querySet->paginate($resourcePerPage);
    }

    function getResourceById($id)
    {
        $resource = $this->model::find($id);
        return $resource;
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

    public function bulkCreate($request) {
        $EntityModel = $this->model;
        $bulks = $request->bulks;
        $tableName =  $EntityModel->getTable();
        $ids = [];
        if(!empty($bulks)) {
            DB::beginTransaction();
            foreach($bulks as $resource) {
                $ids[] = DB::table($tableName)->insertGetId($resource);
            }
            DB::commit();
        }
        if($ids) {
            return response()->json(['data' => $EntityModel::whereIn('id', $ids)->get()], 200);
  
        } else {
            return response()->json(['data' => []], 200);
        }
    }

    public function validatorChecker($_request, $v_fields) {
        $validator = Validator::make($_request, $v_fields);
        if ($validator->fails()) return response()->json(['errors' => $validator->messages()], 422);
        return false;
    }

    function storeResource($request) {
        $EntityModel = $this->model;
        $fields = $EntityModel->postSerializerFields();
        $requestFields = $request->all();

        // Field resolver
        if(method_exists($EntityModel, 'fieldMutation')) {
            $resolverFields = $this->fieldMutation();
            foreach($resolverFields as $resolver) {
                if(isset($requestFields[$resolver['field']])) {
                    $requestFields[$resolver['field']] = $resolver['method']($requestFields[$resolver['field']]);
                }
            }
        }


        // bulk create
        if($request->bulks) {
            if(method_exists($EntityModel, 'fieldsValidator')) {
                $validator = Validator::make($request->bulks[0], $EntityModel->fieldsValidator());
                if ($validator->fails()) return response()->json(['errors' => $validator->messages()], 422);
            }
            return $this->bulkCreate($request);
        }

        if(method_exists($EntityModel, 'fieldsValidator')) {
            $validator = Validator::make($request->all(), $EntityModel->fieldsValidator());
            if ($validator->fails()) return response()->json(['errors' => $validator->messages()], 422);
        }
    
        $resource = new $EntityModel();
        foreach ($fields as $field) {
            if(isset($requestFields[$field])) {
                $resource->$field = $requestFields[$field];
            }
        }

        // one to one field data insert
        foreach($requestFields as $key => $field) {
            if(is_object($field) || is_array($field)) {
                $f_indx = $this->getOne2OneFieldIndex($key);
                if($f_indx > -1) {
                    $relative = $this->one2oneFields[$f_indx];
                    if(method_exists($relative['relative_model'], 'fieldsValidator')){
                        $newInstance = new $relative['relative_model'];
                        $v_fields = $newInstance->fieldsValidator();
                        $cheker = $this->validatorChecker($field, $v_fields);
                        if($cheker) {
                           return $cheker; 
                        } else {
                            // Field resolver
                            if(method_exists($newInstance, 'fieldMutation')) {
                                $resolverFields = $newInstance->fieldMutation();
                                foreach($resolverFields as $resolver) {
                                    if(isset($field[$resolver['field']])) {
                                        $field[$resolver['field']] = $resolver['method']($field[$resolver['field']]);
                                    }
                                }
                            }
                            
                            $r_fields = $newInstance->postSerializerFields();
                            foreach ($r_fields as $_field) {
                                if(isset($field[$_field])) {
                                    $newInstance->$_field = $field[$_field];
                                }
                            }
                            $newInstance->save();
                            $f_key = $relative['self_key'];
                            $resource->$f_key = $newInstance->id; 
                        }
                    }
                }
            }
        }

        $resource->save();
        
        // one to many field data insert
        foreach($requestFields as $key => $field) {
            if(is_object($field) || is_array($field)) {
                $f_indx = $this->getOne2ManyFieldIndex($key);
                if($f_indx > -1) {
                    $relative = $this->one2manyFields[$f_indx];
                    if(method_exists($relative['relative_model'], 'fieldsValidator')){
                        $newInstance = new $relative['relative_model'];
                        $v_fields = $newInstance->fieldsValidator();
                        $cheker = $this->validatorChecker($field[0], $v_fields);
                        if($cheker) {
                           return $cheker; 
                        } else {
                            foreach($field as $idx => $_f) {
                                $_f['division_id'] = 3;
                                $field[$idx] = $_f; 
                            }
                            $relative['relative_model']::insert($field);
                        }
                    }
                }
            }
        }

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
            if ($validator->fails()) return response()->json(['errors' => $validator->messages()], 422);
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
