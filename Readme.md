# Web Service Framework

## Introduction
This framework is based on CodeIgniter 3 and helps you building JWT web services .
Just add controllers and model like this:

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
Validation generates errors in JSON format and exits if validation failed. The error will be generated with generate_output_json function of Api library.

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
...

#### Custom validation
You can use validate function to validate an array of values with your validation rules.
```PHP
$this->api->validate($model_name, $values, $validation_rules);
```

> You can disable validation for a specific controller by setting *$__skip_validation* property of that controller to TRUE.

### Events
You can execute your custom code before or after an specific service using events. Callbacks can be methods of the current controller or any other user defined of built-in methods.
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
This library supports soft deletes and it's enabled by default. You can disable it if not needed.
These configs control soft deletes:

|Config             |Default    |Description
|---                |---        |---
|enable_soft_delete |TRUE       |Enables soft delete
|soft_delete_field  |soft_delete|The name of soft delete field in the database table

And you need to add a soft delete field to each table just like this

```SQL
`soft_delete` ENUM('active','inactive','trash') NOT NULL DEFAULT 'active'
```

You can disable soft delete for a specific table in it's model by setting protected $__skip_soft_delete property in it's model.

Example:
```PHP
protected $__skip_soft_delete = FALSE;
```

### Authentication

## Models

Each model that extends MY_Model supports these functions:
|Function Name  |Supports chaining  |Output type    |Description
|---            |---                |---            |----
|get            |yes                |Object         |Returns one single row
|get_raw        |yes                |Object         |Just like get function without possible overrides.
|get_many       |yes                |Object[]       |Returns multiple rows
|get_all        |yes                |Object[]       |Returns all records
|get_in         |yes                |Object[]       |Returns records using 'WHERE IN' condition and an array
|get_column     |yes                |Object[]       |Creates a n by 1 array of key => value pairs of just one single column
|get_many_many  |yes                |Object[]       |Returns rows based on a n_by_n relation
|where          |yes                |               |
|where_in       |yes                |               |
|sort           |yes                |               |
|join           |yes                |               |
|limit          |yes                |               |
|keyword        |yes                |               |
|add_fields     |yes                |               |
|group_by       |yes                |               |
|ignore_soft_delete|yes             |               |


### Model functions:
#### Get
```PHP
get($value = NULL, $field = 'id')
```
Returns one single row based on given condition. You can call this function without any parameters to get the first record based on chained 'where' conditions.

Examples:
```PHP
// Return the user info with id of 12
$user = $this->user_model->get(12);

// Return the user info based on it's username
$user = $this->user_model->get('john','username');

//Return a field based on a given chained condition
$user = $this->where('credit < 1000')->get();
```
#### Get_raw
```PHP
get($value = NULL, $field = 'id')
```
Just like get() function. It's used when you override get() function and now want to get access to raw record of the table. It's recommended to use get_raw instead of get when you just need to access some fields of that table not anything more.

#### Get_many
```PHP
get_many($value = NULL, $field_name = 'id')
```
#### Get_all
```PHP
get_all()
```
#### Get_in
```PHP
get_in($values, $field = 'id')
```
#### Get_column
```PHP
get_column($column, $key_column = NULL)
```
#### Get_many_many
```PHP
get_many_many($id, $join_table, $dest_table, $src_id, $dest_id)
```


### Chaining
Note: chaining may generate conflicts. For example if you use get() function twice in a model function, Adding chaining functions in the controller just effects the first get(). If adding another query before the main one was needed, first check if it's functionality is maintained before or not. 

## Debugging

