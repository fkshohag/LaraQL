<?php

namespace Shohag\Mixins\ModelMixins;

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
     * @param query $querySet
     * @return query
     */
    public function appliedMullipleFilter($querySet, $filter) {
        $this->paramFilters = $filter;
        $querySet = $querySet->where(function($query){
            $queryFilters = $this->filterSeparator($this->paramFilters); 
            foreach($queryFilters as $qFilter) {
                if(strpos($qFilter['key'], 'b2n_') !== false){
                    $keyMap = explode('_', $qFilter['key']);
                    $key = $keyMap[1];
                    $valueMap = explode('-', $qFilter['value']);
                    $query->whereBetween($key, [$valueMap[0], $valueMap[1]]); 
                } else {   
                    if($this->startsWith($qFilter['key'], 'like~')) {
                        $len = strlen('like~');
                        $key = substr($qFilter['key'], $len);
                        $query->where($key, 'like', '%'.$qFilter['value'].'%');
                    } else {
                        $query->where($qFilter['key'], $qFilter['value']); 
                    }                        
                }
            }
        });
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
        $querySet = $this->model::orderBy('id', $orderBy);
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
}