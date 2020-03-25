# Corona Generic API

At this time we are struggling to survive our life against coronavirus by staying at home. That is the reason why I named this package Corona Generic api.

Laravel API resource is a fantastic feature to make REST API.  We are using it to transform eloquent models to  json responses.

We know every resource route give us seven individual api link and their work is almost similar for every resource. I have make it more generic by using a wrapper So how it will work on?


## Installation

Install the latest version with

```bash
$ composer require coronapi/generic
```

## Basic Model Usage

```php
<?php

namespace App\Models;

use Shohag\Interfaces\CoronaVirus;
use Shohag\Models\CoronaModels;

class Division extends CoronaModels implements CoronaVirus
{
  
    public function __construct()
    {
        parent::__construct($this);
    }

    /**
     * @param NULL
     * @return Array
     */
    public function serializerFields()
    {
        return ['id', 'name','country_id'];
    }

    /**
     * @param NULL
     * @return Array
     */
    public function postSerializerFields()
    {
        return ['name','country_id'];
    }

    /**
     * @param NULL
     * @return Array
     */
    public function fieldsValidator()
    {
        return [
            'name' => 'required',
            'country_id' => 'required'
        ];
    }

     /**
     * @param NULL
     * @return Array
     */
    public function createSerializer() {
        return [
            'direct_fields' => [
                [
                'level' => 'country',
                'model' => Country::class, // Division is depend on country 
                'fields' => ['id', 'name']
                ]
            ]
        ];
    }

}
?>
```

## Basic Controller Usage
```php
<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Shohag\Controllers\CoronaController;

class DivisionController extends CoronaController
{
    // Model pass for dependacy injection purpose
    public function __construct(Division $division)
    {
        $this->EntityInstance = $division;
        parent::__construct(); 
    }
}

```

## Documentation

- 




## About

### Requirements


### Submitting bugs and feature requests


### Author

Fazlul Kabir Shohag - <shohag.fks@gmail.com>

### License

Corona Generic API package is licensed under the MIT License

