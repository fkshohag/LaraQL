<?php

namespace Shohag\Collections;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @author Fazlul Kabir Shohag <shohag.fks@gmail.com>
 */

class CoronaCollection extends ResourceCollection
{

    private $serializerFields = [];
    private $modelInstance = [];


    public function __construct($resource, $serializerFields) {
        $this->serializerFields = $serializerFields;
        parent::__construct($resource); 
    }
   
     /**
     * Create a new resource instance.
     *
     * @param  mixed  $modelInstance
     * @return void
     */
    
    public function getEntityModelInstance($modelInstance) {
        $this->modelInstance = $modelInstance;
    }


    public function SerializerFieldsSet($fields) {
        $this->serializerFields = $fields;
    }

     /**
     * Relationable field extra
     *
     * @param  array  $field
     * @return object
     */
    public function fieldParse($instance, $field) {
        $parts = explode('__', $field);
        foreach($parts as $key => $part) {
            if($key == 0) {
                $relationField = $instance->$part;
            } else {
                if(isset($relationField->$part)) {
                    $relationField = $relationField->$part;
                } else {
                    return null;
                }
            }
        }
        return $relationField;
    }

    /**
     * exac field return
     *
     * @param  string  $field
     * @return string
     */
    public function getFieldKey($fullFieldName) {
        $keys = explode('__', $fullFieldName);
        $field = '';
        foreach($keys as $key) {
                $field .= $key.'_';
        }
        return substr($field, 0, strlen($field)-1);
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection->transform(function ($item) {
                $finalField = array();
                if($this->serializerFields) {
                    foreach ($this->serializerFields as $field) {
                        $finalField[$this->getFieldKey($field)] = $this->fieldParse($item, $field);
                    }
                }
                if($this->modelInstance && method_exists($this->modelInstance, 'getManyToManyFields')) {
                    $m2m_fields = $this->modelInstance->getManyToManyFields();
                    foreach($m2m_fields as $m2m) {
                        $finalField[$m2m['field_name']] = $m2m['model']::where('id', $finalField[$m2m['self']])->get();
                    }
                }
                return $finalField;
            })
        ];
    }
}
