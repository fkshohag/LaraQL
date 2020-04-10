<?php

namespace Shohag\Mixins\ModelMixins;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @author Md.Shohag <Shohag.fks@gmail.com>
 */

Trait QueryMixin {

    private $paramFilters = '';

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
     * @return $tableName
     */
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

    /**
     * @param related $key
     * @param string $field
     * @param querySet $query
     * @param relation $relations
     * @return Query
     */
    public function joinQuery($relatedTo, $key, $value, $query, $relations)
    {
        foreach ($relations as $relation) {
            if ($relatedTo === $relation['key']) {
                $f_table_name = $relation['model']::getTableName();
                $query = $query->join($f_table_name, $f_table_name.'.'.$relation['related'], '=', $this->tableName.'.'.$relation['self']);
                $query->where($f_table_name.'.'.$key, $value);
                break;
            }
        }
        return $query;
    }

    /**
     * @param query $querySet
     * @return query
     */
    public function appliedMullipleFilter($querySet, $filter)
    {
        $this->paramFilters = $filter;
        $queryFilters = $this->filterSeparator($this->paramFilters);

        foreach ($queryFilters as $qFilter) {
            if (strpos($qFilter['key'], 'b2n_') !== false) {

                $keyMap = explode('_', $qFilter['key']);
                $key = $keyMap[1];
                $valueMap = explode('-', $qFilter['value']);
                $querySet->whereBetween($this->tableName.'.'.$key, [$valueMap[0], $valueMap[1]]);
            } else {
                if ($this->startsWith($qFilter['key'], 'like~')) {
                    $len = strlen('like~');
                    $key = substr($qFilter['key'], $len);
                    $querySet->where($this->tableName.'.'.$key, 'like', '%' . $qFilter['value'] . '%');
                } else {
                    // Foreign Key filter apply
                    if ($this->firstPatternMatching($qFilter['key'], '__')) {
                        $key_relations = explode('__', $qFilter['key']);
                        $prefix_key = $key_relations[0];
                        $key = $key_relations[1];

                        if (method_exists($this, 'relations')) {
                            $querySet = $this->joinQuery($prefix_key, $key,  $qFilter['value'], $querySet, $this->relations());
                        }
                        $selectFields = [];
                        $serializerFields = $this->SerializerFields();
                        foreach($serializerFields as $field) {
                            if(!$this->firstPatternMatching($field, '__')) {
                                $selectFields[] = $this->tableName.'.'.$field;
                            }
                        }
                        $querySet->select($selectFields);
                    } else {
                        $querySet->where($this->tableName.'.'.$qFilter['key'], $qFilter['value']);
                    }
                }
            }
        }
        return $querySet;
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
        if ($validator->fails()) return response()->json(['errors' => $validator->messages()]);
        return false;
    }
}