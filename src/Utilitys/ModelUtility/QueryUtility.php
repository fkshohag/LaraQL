<?php

namespace Shohag\Utilitys\ModelUtility;

use App\GenericSolution\GenericModelFields\Fields;

/**
 * @author Md.Shohag <Shohag.fks@gmail.com>
 */

 trait QueryUtility {
    public function getOne2OneFieldIndex($relativeField) {
        if (property_exists($this, 'one2oneFields')) {
            foreach($this->one2oneFields as $index => $field) {
                if($field['associate_with'] == $relativeField) {
                    return $index;
                }
            }
        }
        return -1;
    }
    public function getOne2ManyFieldIndex($relativeField) {
        if (property_exists($this, 'one2manyFields')) {
            foreach($this->one2manyFields as $index => $field) {
                if($field['associate_with'] == $relativeField) {
                    return $index;
                }
            }
        }
        return -1;
    }
 }