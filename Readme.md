# Web Service Framework

## Introduction
This framework is based on CodeIgniter 3. It helps you writing JWT web services easily.
Defining a new service is so easy. just add controllers and model like this:

**Controller:**

```PHP
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends MYController {

    public $__model_name = 'my_model_name';
```
**Model:**

```PHP
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends MY_Model {

    public $__table_name = 'table_name';
```
And these services are available:

- /list
- /single($id)
- /add
- /update($id)
- /delete($id)

## Controllers

### Validation
Validation generates errors in JSON format and exits if validation failed. The error is generating with generate_output_json function of Api library.

Validation can be defined for each service as bellow:
```PHP
    protected $__{Service Name}_validation = [
        'field_name' => ['rule_1', 'rule_2', ...]
        ...
    ];
```

Example:
```PHP
    protected $__list_validation = [
        'username' => ['required','is_numeric','is_unique'],
        'email; => ['is_email']
    ];
```
#### List of built-in validation rules
Validation rules are just like CodeIgniter validation rules with a little changes

|Rule       |Parameters |Description
|---        |---        |---
|required   |none       |Must
|is_unique  |field_name |Checks if the field is unique or not

#### Custom validation
You can use validation function to validate an array of values with your validation rules.
```PHP
$this->api->validate($model_name, $values, $validation_rules);
```

> You can disable validation for an specific controller by setting *$__skip_validation* property of that controller to FALSE.

### Events
You can execute your custom code before or after an specific service using events. Callbacks can be methods of the current controller or any other user defined of built-in method.
> Callbacks can change the input parameters but this is not mandatory.

Defining events is just like this:
```PHP
   protected $events = [
        '{event name}' => '{callback function}'
    ];
```
Example:
```PHP
    protected $events = [
        'before_list' => 'before_list_callback',
        'after_update' => 'after_update_callback'
    ];

    protected function before_list_callback($params)
    {
        // Do some stuff
        return $params;
    }
```

List of available events:

|event          |Parameters
|---            |---
|before_list    |input parameters of the list    
|after_list     |results
|before_add     |record
|after_add      |record with included 'id'
|before_update  |fields to update including the given 'id'
|after_update   |fields to update including the given 'id'
|before_delete  |an array of ids that should get deleted
|after_delete   |an array of ids that should get deleted

### Soft delete
This library supports soft deletes and it's enabled by default. You can disable if do not need it or want to implement it yourself.

### Authentication

##Models

Each model that extends MY_Model supports these functions:
|Function Name  |Supports chaining|Output type  |Description
|---            |---              |---          |----
|get            |yes              |Object       |Returns one single row
|get_raw        |yes              |Object       |Returns
|get_many       |yes              |Object[]     |Returns multiple rows
|get_all        |yes              |Object[]     |Returns all records
|get_in         |yes              |Object[]     |Returns records using 'WHERE IN' condition and an array
|get_column     |yes              |Object[]     |Creates a nx1 array of key => column pairs

### Model functions


### Chaining


## Debugging

