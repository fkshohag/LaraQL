<?php

namespace Shohag\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @author Fazlul Kabir Shohag <shohag.fks@gmail.com>
 */
class LaraQLResource extends JsonResource
{
    private $serializerFields = null;

    public function __construct($resource, $serializerFields) {
        $this->serializerFields = $serializerFields;
        parent::__construct($resource);
    }

    public function fieldParse($field) {
        $parts = explode('__', $field);
        foreach($parts as $key => $part) {
            if($key == 0) {
                $relationField = $this->$part;
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

    public function getFieldKey($fullFieldName) {
        $keys = explode('__', $fullFieldName);
        $field = '';
        foreach($keys as $key) {
                $field .= $key.'_';
        }
        return substr($field, 0, strlen($field)-1);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {
        $fields = array();
        if($this->serializerFields) {
            foreach ($this->serializerFields as $field) {
                $fields[$this->getFieldKey($field)] = $this->fieldParse($field);
            }
        }
        if($fields) {
            return $fields;
        } else {
           return parent::toArray($request);
        }
    }
}
