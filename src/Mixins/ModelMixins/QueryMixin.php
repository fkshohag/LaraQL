<?php

namespace Shohag\Mixins\ModelMixins;

/**
 * @author Md.Shohag <Shohag.fks@gmail.com>
 */

trait QueryMixin
{

    private $paramFilters = '';
    private $uniqueTableList = [];

    /**
     * String separator
     * @param string $string
     * @param array 
     */

    public function filterSeparator($string): array
    {
        $parts = explode(',', $string);
        $filters = [];
        foreach ($parts as $part) {
            $second_part = explode(':', $part);
            $filters[] = [
                'key' =>  $second_part[0],
                'value' => $second_part[1]
            ];
        }
        return $filters;
    }

    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    /**
     * @param string $string string
     * @param string $string startString
     * @return boolean
     */
    public function startsWith($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    /**
     * @param string $string string
     * @param string $string startString
     * @return boolean
     */
    public function endsWith($string, $endString)
    {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }

    public function firstPatternMatching($string, $pattern): bool {
        $len = strlen($string) - strlen($pattern);
        $p_len = strlen($pattern);
        for($i = 0; $i <= $len; $i++) {
            $counter = 0;
            for($j = $i; $j < $i+$p_len; $j++) {
                if($string[$j] == $pattern[$counter]) {
                    $counter++;
                    if($counter == $p_len) return true;
                    continue;
                } else {
                   break;
                }
            }
        }
        return false;
    }

    /**
     * @param query $querySet
     * @return query
     */
    public function joinQuery($field, $model){
        if(method_exists($model, 'relations')){
            if($this->firstPatternMatching($field, '__')) {
                $field_keys = explode('__', $field);
                $key = $field_keys[0];
                $field = $field_keys[1];
                foreach($model::relations() as $relation){ 
                    if($key == $relation['key']) {
                        $field = (new $relation['model'])->getTable().'.'.$field;
                        return $field;
                    }
                }
            } else {
                return $this->getTableName().'.'.$field;
            }
        }
        return $this->getTableName().'.'.$field;
        
    }

    public function selectQueryField(){
        $selectFields = [];
        $selectFields[] = 'id';
        $serializerFields = $this->PostSerializerFields();
        foreach($serializerFields as $field) {
            if(!$this->firstPatternMatching($field, '__')) {
                $selectFields[] = $this->tableName.'.'.$field;
            }
        }
        return $selectFields;
    }
    public function appliedMullipleFilter($querySet, $filter)
    {
        $this->paramFilters = $filter;
        $queryFilters = $this->filterSeparator($this->paramFilters);

        if(method_exists($this, 'relations')){
            foreach($this::relations() as $relation){
                $f_table_name = $relation['model']::getTableName();
                $b_table_name = $relation['base_model']::getTableName();
                $querySet->join($f_table_name, $f_table_name.'.'.$relation['related'], '=', $b_table_name.'.'.$relation['base_reation']);
            }
        }
        foreach ($queryFilters as $qFilter) {
            if (strpos($qFilter['key'], '__b2n') !== false) {
                $keyMap = explode('__b2n', $qFilter['key']);
                $key = $keyMap[0];
                $valueMap = explode('~', $qFilter['value']);

                $key = $this->joinQuery($key, $this);
                $querySet->whereBetween($key, [$valueMap[0], $valueMap[1]]);
            } else if($this->firstPatternMatching($qFilter['key'], '__gte')) {
                $keyMap = explode('__gte', $qFilter['key']);
                $key = $keyMap[0];
                $key = $this->joinQuery($key, $this);
                $querySet->where($key, '>=', $qFilter['value']);
            } 
            else if($this->firstPatternMatching($qFilter['key'], '__lte')) {
                $keyMap = explode('__lte', $qFilter['key']);
                $key = $keyMap[0];
                $key = $this->joinQuery($key, $this);
                $querySet->where($key, '<=', $qFilter['value']);
            } 
            else if ($this->startsWith($qFilter['key'], 'like~')) {
                $len = strlen('like~');
                $key = substr($qFilter['key'], $len);
                $key = $this->joinQuery($key, $this);
                $searchData = strtolower($qFilter['value']);
                $querySet->whereRaw('lower('.$key.') like (?)',["%{$searchData}%"]);
            } 
            else if ($this->startsWith($qFilter['key'], 'in~')) {
                $len = strlen('in~');
                $key = substr($qFilter['key'], $len);
                $key = $this->joinQuery($key, $this);
                $searchData = explode("--",$qFilter['value']);
                $querySet->wherein($key, $searchData);
            }
            else {
                $key = $qFilter['key'];
                $key = $this->joinQuery($key, $this);
                $querySet->where($key, $qFilter['value']);
            }
        }
        return $querySet;
    }
}
