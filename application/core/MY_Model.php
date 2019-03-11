<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Model extends CI_Model
{
    public $__table_name;
    public $__table_alias = '__table_alias';

    protected $__with_inactive = FALSE;
    protected $__sort_field = '';
    protected $__offset = 0;
    protected $__keyword = "";
    protected $__search_fields = [];
    protected $__skip_soft_delete = FALSE;
    protected $__list_fields = "";
    protected $__where = [];
    protected $__join = [];
    protected $__group_by = [];
    protected $__limit = 100000000;

    public function __construct()
    {
        parent::__construct();

        // Check if table_name is declared in the child model
        if (empty($this->__table_name)) {
            $error = "Table name is not declared for model: '" . get_called_class() . "'";
            log_message('error', $error);
            exit($error);
        };

        // Update database names if there is database infos available
        if (isset($this->token_data->db_postfix))
        {
            // Compile the table name
            $this->__table_name = $input = $this->api->compile_db_name($this->__table_name, $this->token_data);
        }
    }
    
    /**
     * Inserts a new record and returns it's id or 'true' if the table does not support auto increment id
     */
    public function insert($fields)
    {
        // If there is nothing to insert, exit
        if (empty($fields)) {
            log_message('error', "Cannot insert into {$this->__table_name} table without any value");
            return FALSE;
        }

        // Do insert. If failed, generate error
        if($this->db->insert($this->__table_name, $fields) === FALSE)
        {
            // Return database error
            $this->api->generate_output_json(0);
        }
        
        // If the table does not support auto increment, the result is 0
        $inserted_id = $this->db->insert_id();
        $inserted_id = $inserted_id == 0 ? TRUE : $inserted_id;
        
        return $inserted_id;
    }

    /**
     * Inserts an array of records and returns true on success
     *
     * @param array $fields
     * @return int Inserted id
     */
    public function insert_batch(array $fields)
    {
        // If there is nothing to insert, exit
        if (empty($fields)) {
            log_message('error', "Cannot insert into {$this->__table_name} table without any value");
            return FALSE;
        }

        // Do insert. If failed, generate error
        if($this->db->insert_batch($this->__table_name, $fields) === FALSE)
        {
            // Return database error
            $this->api->generate_output_json(0);
        }
        
        // If the table does not support auto increment, the result is 0
        return TRUE;
    }

    /**
     * Inserts a new row. if duplcate, updates the row with 
     *
     * @param array $fields Field values to update
     * @return void
     */
    public function insert_update($fields)
    {
        $this->load->model('contact_element_model');

        // If there is nothing to insert, exit
        if (empty($fields)) {
            log_message('error', "Cannot insert into {$this->__table_name} table without any value");
            return FALSE;
        }

        // Generate insert command
        $insert_command = $this->db->insert_string($this->contact_element_model->__table_name, $fields);
        
        // Generate update command
        $temp = [];
        array_walk($fields, function(&$value, $key) use(&$temp){
            $temp[] = "`$key` = \"$value\"";
        });
        $update_part = implode(',', $temp);

        // Generate final query
        $sql = "$insert_command ON DUPLICATE KEY UPDATE $update_part";

        // Do insert/update. If failed, show error
        if($this->db->query($sql) === FALSE)
        {
            // Return database error
            $this->api->generate_output_json(0);
        }
        
        // If the table does not support auto increment, the result is 0
        $inserted_id = $this->db->insert_id();
        $inserted_id = $inserted_id == 0 ? TRUE : $inserted_id;
        
        return $inserted_id;
    }
    
    /**
     * Updates one record with given id
     */
    public function update($fields, int $id, $escape = NULL)
    {
        // Do update
        return $this->limit(1)->update_by($fields, ['id'=> $id], $escape);
    }

    /**
     * Updates one record with given condition
     */
    public function update_by($fields, array $conditions, $escape = NULL)
    {
        // If there is nothing to update, exit
        if (empty($fields)) {
            log_message('error', "Cannot update {$this->__table_name} table with empty 'values'");
            return false;
        }

        // Include inactive items if requested
        $this->where_soft_delete($this->__table_alias);
        $this->add_join();
        $this->add_where();

        // Add limitation
        $this->db->where($conditions);

        // Do update
        $this->db->set($fields, null, $escape);
        if( $this->db->update($this->__table_name . " AS {$this->__table_alias}"))
        {
            return $this->db->affected_rows();
        }
        else
        {
            // Return database error
            $this->api->generate_output_json(0);
        }
    }

    /**
     * Gets some records from database
     */
    public function list()
    {
        // generate a subquery that gets total record counts from the table
        $this->where_soft_delete($this->__table_alias);
        $this->add_keyword();
        $this->add_join();
        $this->add_where();

        $total_query = $this->db
            ->select('COUNT(*) as total_count', FALSE)
            ->from($this->__table_name.' AS '.$this->__table_alias)
            ->get_compiled_select();
            
        // Get results from database
        $this->set_select_fields();
        $this->where_soft_delete($this->__table_alias);
        $this->add_keyword();
        $this->add_sort($this->__table_alias);
        $this->add_limit();
        $this->add_join();
        $this->add_where();
        $this->add_group_by();

        $data = $this->db
            ->select("($total_query) AS __total_count", FALSE)
            ->from($this->__table_name . " AS {$this->__table_alias}", FALSE)
            ->get()
            ->result();

        // Calculate total count
        if(count($data) > 0)
        {
            $result['total_records'] = $data[0]->__total_count;

            // Remove total record column from result
            array_walk($data, function(&$record){
                unset($record->__total_count);
            });
        }
        else
        {
            $result['total_records'] = 0;
        }

        $result['total_pages'] = (int)ceil($result['total_records'] / $this->__limit);
        $result['data'] = $data;

        // Return results as object
        return (object)$result;
    }

    /**
     * Gets an array of ids and delete them
     *
     * @param int[] $ids Ids to delete
     * @return int Number of affected rows
     */
    public function delete_many($ids = NULL)
    {
        // Add where condition if there is any ids
        $this->db->where_in('id', $ids);

        // If there is not any where condition and no ids
        // All the table will get lost
        // Return error
        $temp_sql = $this->db->get_compiled_select(NULL, FALSE);
        $has_where = stripos($temp_sql, 'where') !== FALSE;
        if(! $has_where)
        {
            $this->api->generate_output_json(-136);
        }

        // If soft delete is not enabled or 'soft_delete_field' is enabled for the current model, delete the record
        if($this->config->item('enable_soft_delete') === FALSE OR $this->__skip_soft_delete === TRUE )
        {
            // Hard delete items
            $success = $this->db
                ->delete($this->__table_name);
    
            // If database error, return false
            if (!$success) {
                return FALSE;
            }
        }
        // If soft delete is enabled, set the soft delete field as trash
        else
        {
            $soft_delete_field = $this->config->item('soft_delete_field') ?? 'soft_delete';
    
            // Soft delete items
            $success = $this->db
                ->set($soft_delete_field, DB_SOFT_DELETE_TRASH)
                ->update($this->__table_name . " AS {$this->__table_alias}");
    
            // If database error, return false
            if (!$success) {
                return FALSE;
            }
        }

        // Return affected rows
        return $this->db->affected_rows();
    }

    public function delete_by($conditions)
    {
        $this->db->where($conditions);
        return $this->delete_many();
    }

    public function delete($id)
    {
        return $this->delete_by(['id'=>$id]);
    }

    /**
     * Checks if the given value exists in the table
     * @param $value A single item or an array of values
     * @param string $field_name
     * @param null $except_id This id will not be processed during check
     * @return bool
     */
    public function has($values, $field_name = 'id', $except_id = NULL)
    {
        // Convert single values to array
        // This way the function can accept both arrays and single items
        if(! is_array($values))
        {
            $values = [$values];
        }

        // Add exception id if needed
        if (!empty($except_id)) {
            $this->db->where("id != $except_id");
        }

        // Include inactive items if requested
        $this->where_soft_delete($this->__table_alias);
        $this->add_join();
        $this->add_where();

        return $this->db
                ->from($this->__table_name  . " AS {$this->__table_alias}", FALSE)
                ->where_in($field_name, $values)
                ->count_all_results() == count($values);
    }

    /**
     * Returns multiple values based on a condition
     * @param $value
     * @param $field_name
     */
    public function get_many($value = NULL, $field_name = 'id')
    {
        // Include inactive items if requested
        $this->set_select_fields();
        $this->where_soft_delete($this->__table_alias);
        $this->add_join();
        $this->add_where();
        $this->add_limit();
        $this->add_group_by();

        // If there is a condition, add it
        if($value !== NULL)
        {
            $this->db->where($this->__table_alias .'.'. $field_name, $value);
        }
        
        return $this->db
            ->from($this->__table_name  . " AS {$this->__table_alias}", FALSE)
            ->get()
            ->result();
    }

    /**
     * Returns a single row from the table
     * @param int $id The id of the field
     */
    public function get($value = NULL, $field = 'id')
    {
        return $this
            ->limit(1)
            ->get_many($value, $field)[0] ?? NULL;
    }

    /**
     * Returns a single row from the table
     */
    public function get_all()
    {
        return $this->get_many();
    }

    /**
     * Returns rows with the given ids
     * @param $ids
     */
    public function get_in($values, $field = 'id')
    {
        return $this
            ->where_in($field, $values)
            ->get_many();
    }

    public function get_column($column, $key_column = NULL)
    {
        $results = $this
            ->add_fields($key_column)
            ->add_fields($column)
            ->get_many();

        // Return the column
        return array_column($results, $column, $key_column);
    }

    /**
     * Get one row without any joins and conditions
     */
    public function get_raw($id = NULL)
    {
        return self::get($id);
    }

    /**
     * Returns multiple rows in a many to many relation
     * @param $id The id of source table to be searched
     * @param $join_table The join table
     * @param $dest_table Te destination table that has results
     * @param $src_id The id column of the source table in the join table
     * @param $dest_id The id column of the destination table in the join table
     */
    public function get_many_many($id, $join_table, $dest_table, $src_id, $dest_id)
    {
        // Include inactive items if requested
        $this->where_soft_delete('source_table');
        $this->where_soft_delete('join_table');
        $this->where_soft_delete('dest_table');

        // Add limit
        $this->add_limit();
                
        // add sort
        $this->add_sort('dest_table');

        return $this->db
            ->select('dest_table.*')
            ->select('join_table.*')
            ->from("{$this->__table_name} AS source_table")
            ->join("{$join_table} AS join_table", "source_table.id = join_table.{$src_id}", 'LEFT')
            ->join("{$dest_table} AS dest_table","join_table.{$dest_id} = dest_table.id",'RIGHT')
            ->where("source_table.id = {$id}")
            ->get()
            ->result();
    }

    /**
     * Retuns true if the current user owns the item with the current id
     * Just if the current table have a 'user_id' field
     */
    public function user_owns($ids)
    {
        // If there is just one id, convert it to array
        if(! is_array($ids))
        {
            $ids = [$ids];
        }

        return $this->db
            ->from($this->__table_name)
            ->where_in('id', $ids)
            ->where('user_id', $this->token_data->user_id)
            ->count_all_results() == count($ids);
    }

    public function where_in($key = NULL, $values = NULL, $escape = NULL)
    {
        $this->db->where_in($this->__table_alias.'.'.$key, $values, $escape);
        return $this;
    }

    public function where($key, $value = NULL, $escape = NULL)
    {
        $this->__where[] = [
            'key' => $key,
            'value' => $value,
            'escape' => $escape,
        ];
        return $this;
    }

    public function add_where()
    {
        // Add all where conditions
        foreach($this->__where as $where)
        {
            $this->db->where($where['key'] ,$where['value'] ,$where['escape']);
        }
        return $this;
    }

    // Include inactive items in the result
    public function with_inactive()
    {
        $this->__with_inactive = TRUE;
        return $this;
    }

    public function where_soft_delete($table_alias = "")
    {
        // Skip, if is not enabled in the configs or it's not needed for this model
        if ($this->config->item('enable_soft_delete') !== TRUE OR $this->__skip_soft_delete === TRUE)
        {
            return $this;
        }

        // If table alias is not defined, use the table name
        if (empty($table_alias))
        {
            $table_alias = $this->__table_alias;
        }

        $soft_delete_field = $this->config->item('soft_delete_field') ?? 'soft_delete';

        if( $this->__with_inactive )
        {
            // Include inactive items if requested
            $this->db->where("$table_alias.$soft_delete_field != ", DB_SOFT_DELETE_TRASH, FALSE);
        }
        else
        {
            // If not, do not show inactive items
            $this->db->where("$table_alias.$soft_delete_field", DB_SOFT_DELETE_ACTIVE);
        }
        return $this;
    }

    public function sort($field)
    {
        $this->__sort_field = $field;
        return $this;
    }

    public function add_sort($table_alias = "")
    {
        // If there is no sort defined, return
        if(empty($this->__sort_field))
        {
            return $this;
        }

        // If table alias is not defined, user table name
        if (empty($table_alias))
        {
            $table_alias = $this->__table_alias;
        }

        $this->db->order_by($table_alias . '.' .$this->__sort_field);
        return $this;
    }

    public function join($table, $cond, $type = '', $escape = NULL)
    {
        $this->__join[] = [
            'table' => $table,
            'cond' => $cond,
            'type' => $type,
            'escape' => $escape,
        ];
        return $this;
    }

    public function add_join()
    {
        // Join all
        foreach($this->__join as $join)
        {
            $this->db->join($join['table'] ,$join['cond'] ,$join['type'] ,$join['escape']);
        }
        return $this;
    }

    public function limit($limit, $offset = 0)
    {
        $this->__limit = $limit;
        $this->__offset = $offset;
        return $this;
    }

    public function add_limit()
    {
        // Add limit if needed
        if ($this->__limit !== FALSE)
        {
            $this->db->limit($this->__limit, $this->__offset);
        }
        return $this;
    }

    public function add_fields($__list_fields)
    {
        
        $this->__list_fields = $this->__list_fields ? $this->__list_fields .','. $__list_fields : $__list_fields;
        return $this;
    }

    public function set_select_fields()
    {
        // Empty string means *
        if(empty($this->__list_fields))
        {
            $this->db->select($this->__table_alias.'.*');
        }
        else
        {
            $this->db->select($this->__list_fields);
        }
        return $this;
    }

    public function ignore_soft_delete()
    {
        $this->__skip_soft_delete = TRUE;
        return $this;
    }

    public function keyword($keyword)
    {
        $this->__keyword =  $this->db->escape_str($keyword);
        return $this;
    }

    /**
     * Add some fields to search field list
     * These fields are used in keyword search function for lists
     */
    public function add_search_fields(array $fields)
    {
        $this->__search_fields = array_merge($this->__search_fields, $fields);
        return $this;
    }

    public function add_keyword()
    {
        $search_fields = $this->__search_fields;

        // If there is a keyword, Add it to the query
        if (!empty($this->__keyword)) {
            
            // If the search fields are not defined, read all fields of the table
            if (empty($search_fields))
            {
                // Get the field names
                $search_fields = $this->db->list_fields($this->__table_name);

                // Remove special fields
                $search_fields = array_diff($search_fields, $this->config->config['db_search_skip_fields']);
            }

            // Add filter to each database field
            $this->db->group_start(); // grouping added parenteses to the query
            foreach ($search_fields as $field)
            {
                $this->db->or_like($field, $this->__keyword);
            }
            $this->db->group_end();
        }
        return $this;
    }

    public function select($select = '*', $escape = NULL)
    {
        $this->db->select($select, $escape);
        return $this;
    }

	public function group_by($by)
    {
        $this->__group_by[] = $by;
        return $this;
    }

    protected function add_group_by()
    {
        foreach($this->__group_by as $group)
        {
            $this->db->group_by($group);
        }
    }
}

/* End of file MY_Model.php */
