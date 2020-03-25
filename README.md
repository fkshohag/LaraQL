# Corona Generic API

At this time we are struggling to survive our life against coronavirus by staying at home. That is the reason why I named this package Corona Generic api.

Laravel API resource is a fantastic feature to make REST API.  We are using it to transform eloquent models to  json responses.

We know every resource route give us seven individual api link and their work is almost similar for every resource. I have made it more generic by using a wrapper!


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
use Shohag\Models\CoronaModel;

class Division extends CoronaModel implements CoronaVirus
{
  
    public function __construct()
    {
        parent::__construct($this);
    }
    /**
     * @param NULL
     * @return $mixin
     */
    public function country() {
        return $this->belongsTo(Country::class, 'country_id', 'id')->select(['id', 'name']);
    } 

    /**
     * @param NULL
     * @return Array
     */
    public function serializerFields(): array
    {
        return ['id', 'name','country_id', 'country__name'];
    }

    /**
     * @param NULL
     * @return Array
     */
    public function postSerializerFields(): array
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
                'model' => Country::class, // country_id foreign key of division table
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

## Basic Route Usage
```
Route::resource('/divisions', 'DivisionController');

Verb          Path                        Action  Route Name
GET           /divisions                   index   divisions.index
GET           /divisions/create            create  divisions.create
POST          /divisions                   store   divisions.store
GET           /divisions/{id}              show    divisions.show
GET           /divisions/{id}/edit         edit    divisions.edit
PUT|PATCH     /divisions/{id}              update  divisions.update
DELETE        /divisions/{id}              destroy divisions.destroy

```

## Documentation

-- Our API is ready to access so, in the below section will be describe how it will help us!.

* divisions GET `/api/divisions`
* Response
```
{
    "data": [
        {
            "id": 3,
            "name": "Dhaka",
            "country_id": 1,
        },
        {
            "id": 2,
            "name": "Khulna",
            "country_id": 2,
        }
    ],
    "links": {
        "first": "http://127.0.0.1:8002/api/divisions?page=1",
        "last": "http://127.0.0.1:8002/api/divisions?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://127.0.0.1:8002/api/divisions",
        "per_page": 10,
        "to": 3,
        "total": 3
    }
}
```
* divisions with single filter: GET `api/divisions?filters=country_id:2`
* Response
```
{
    "data": [
        {
            "id": 2,
            "name": "Khulna",
            "country_id": 2,
        }
    ],
    "links": {
        "first": "http://127.0.0.1:8002/api/divisions?page=1",
        "last": "http://127.0.0.1:8002/api/divisions?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://127.0.0.1:8002/api/divisions",
        "per_page": 10,
        "to": 3,
        "total": 3
    }
}
```

* divisions with multiple filter: GET `api/divisions?filters=country_id:2,name:Khulna`
* Response
```
{
    "data": [
        {
            "id": 2,
            "name": "Khulna",
            "country_id": 2,
        }
    ],
    "links": {
        "first": "http://127.0.0.1:8002/api/divisions?page=1",
        "last": "http://127.0.0.1:8002/api/divisions?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://127.0.0.1:8002/api/divisions",
        "per_page": 10,
        "to": 3,
        "total": 3
    }
}
```
* divisions like filter: GET `api/divisions?filters=country_id:2,like~name:ulna`
* Response
```
{
    "data": [
        {
            "id": 2,
            "name": "Khulna",
            "country_id": 2,
        }
    ],
    "links": {
        "first": "http://127.0.0.1:8002/api/divisions?page=1",
        "last": "http://127.0.0.1:8002/api/divisions?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://127.0.0.1:8002/api/divisions",
        "per_page": 10,
        "to": 3,
        "total": 3
    }
}
```

* divisions like,queryFields filter: GET `/api/divisions?filters=country_id:1,like~name:khu&queryFields=id,name`
* Response
```
{
    "data": [
        {
            "id": 2,
            "name": "Khulna",
        }
    ],
    "links": {
        "first": "http://127.0.0.1:8002/api/divisions?page=1",
        "last": "http://127.0.0.1:8002/api/divisions?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://127.0.0.1:8002/api/divisions",
        "per_page": 10,
        "to": 3,
        "total": 3
    }
}
```

* divisions like,queryFields with foreignkey filter: GET `/api/divisions?filters=country_id:1,name:Bangladesh&queryFields=id,name,county_id,country__name`
* Response
```
{
    "data": [
        {
            "id": 2,
            "name": "Khulna",
            "country_id": 1,
            "country_name": "Bangladesh"
        }
    ],
    "links": {
        "first": "http://127.0.0.1:8002/api/divisions?page=1",
        "last": "http://127.0.0.1:8002/api/divisions?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://127.0.0.1:8002/api/divisions",
        "per_page": 10,
        "to": 3,
        "total": 3
    }
}
```

* divisions single resource create: POST: `/api/divisions`
* body
```
{
    "name": "Mymensingh",
    "company_id": 1,
}
```

* divisions bulk resource create: POST: `/api/divisions`
* body
```
{
  "bulks": [
    {
      "name": "Sylhet",
      "country_id": 1,
      
    },
    {
      "name": "Rajshahi",
      "country_id": 1
    }
  ]
}
```

* divisions create: GET: `/api/divisions/create`
* Response
```
// Model createSerializer() method will be work here
{
    "data": {
        "country": [
            {
                "id": 1,
                "name": "Bangladesh"
            }
        ]
    }
}
```


### Author

Fazlul Kabir Shohag - <shohag.fks@gmail.com>

### License

Corona Generic API package is licensed under the MIT License

