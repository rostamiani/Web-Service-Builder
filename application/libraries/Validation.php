<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Validation
{
    protected $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->library('api');
	}
	
    /**
     * Validates input with given rules
     */
    public function run($values, array $field_rules, $except_id = NULL/*, $parent_field = ''*/)
    {
        // For each field
        foreach ($field_rules as $field => $rules) {

            // Run all validation rules
            foreach ($rules as $rule) {

				// // If the rule is an array itself, run the rules for it recuresively
				// if(is_array($rule))
				// {
				// 	// Run the rules of the array itsef if there is any
				// 	// These rules are defined with the key: '_self'
				// 	$this->run($values->$field, [$field => $rule['_self']], $except_id);

				// 	// _self rules are executed. remove them
				// 	unset($rule['_self']);

				// 	// Run the rest of array rules
				// 	$this->run($values->$field, $rules, $except_id, $field);
				// }
            
                $result = FALSE;
                $value = $values->$field ?? NULL;

				// Ignore empty, non-required inputs with a few exceptions ...
				if ($value === NULL && ! in_array($rule, array('required', 'isset', 'matches'), TRUE))
				{
					continue;
				}
			
				// Strip the parameter (if exists) from the rule
                // Rules can contain a parameter: max_length[5]
				$param = FALSE;
				$complete_rule = $rule;
                if ( preg_match('/(.*?)\[(.*)\]/', $rule, $match))
                {
                    $rule = $match[1];
                    $param = $match[2];
                }

                // is_unique needs database
                if($rule == 'is_unique')
                {
                    $result = $this->is_unique($value, $field, $except_id);
                }
                // Check if this method exists in this class
                elseif (method_exists($this, $rule))
                {
                    // Execute the method
                    $result = $this->$rule($value, $param);
                }
                // Check if this rule is a function
                elseif (function_exists($rule))
                {
                    // Execute the function
                    // Native PHP functions issue warnings if you pass them more parameters than they use
					$result = ($param !== FALSE) ? $rule($value, $param) : $rule($value);
                }
                // Check if this method exists in the controller
                elseif (method_exists($this->ci, $rule))
                {
                    // Execute the method
                    $result = $this->ci->$rule($value, $param, $values);
                }
                else{
                    // The rule does not exist. Return error
                    log_message('error',"Validation rule '$rule' does not exist. table name: '$model_name'.");
                    $this->ci->api->generate_output_json(-111,['data'=>$rule]);
                }

                // If validation failed, display error
                if($result === FALSE)
                {
                    $this->ci->api->generate_output_json(-108,  ['data' => [$field => $complete_rule]]);
                }
            }
        }

        // Show error if there is an additional field that is not defined, show error
        if (count($additional_fields = array_diff_key((array)$values ?? [], $field_rules)) > 0)
        {
            $this->ci->api->generate_output_json(-113, ['data' => array_keys($additional_fields)]);
        }

        // If no error, return success
        return TRUE;
    }

	/**
	 * Required
	 *
	 * @param	string
	 * @return	bool
	 */
	public function required($str)
	{
		return is_array($str)
			? (empty($str) === FALSE)
			: (trim($str) !== '');
	}

	// --------------------------------------------------------------------

	/**
	 * Not empty
	 *
	 * @param	string
	 * @return	bool
	 */
	public function not_empty($str)
	{
        if($str === NULL)
            return TRUE;

		return is_array($str)
			? (empty($str) === FALSE)
			: (trim($str) !== '');
	}

	// --------------------------------------------------------------------

	/**
	 * Performs a Regular Expression match test.
	 *
	 * @param	string
	 * @param	string	regex
	 * @return	bool
	 */
	public function regex_match($str, $regex)
	{
		return (bool) preg_match($regex, $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Match one field to another
	 *
	 * @param	string	$str	string to compare against
	 * @param	string	$field
	 * @param	string	$values
	 * @return	bool
	 */
	public function matches($str, $field, $values)
	{
		return $str === ($values[$field] ?? NULL);
	}

	// --------------------------------------------------------------------

	/**
	 * Differs from another field
	 *
	 * @param	string	$str	string to compare against
	 * @param	string	field
	 * @param	string	$values
	 * @return	bool
	 */
	public function differs($str, $field, $values)
	{
		return $str !== ($values[$field] ?? NULL);
	}

	// --------------------------------------------------------------------

	/**
	 * Is Unique
	 *
	 * Check if the input value doesn't already exist
	 * in the specified database field.
	 *
	 * @param	string	$str
	 * @param	string	$field
	 * @return	bool
	 */
	public function is_unique($str, $field, $except_id = NULL)
	{
        // If there is not any models defined, return false
        $model_name = $this->ci->__model_name;
        if(empty($model_name))
        {
            return FALSE;
        }
        
        $this->ci->load->model($model_name);

        // If no access to database, return false
        if(! isset($this->ci->{$model_name}))
        {
            return FALSE;
        }

        // Check if the value is unique
        return ! $this->ci->{$model_name}
            ->has($str, $field, $except_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Minimum Length
	 *
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	public function min_length($str, $val)
	{
		if ( ! is_numeric($val))
		{
			return FALSE;
		}

		return ($val <= mb_strlen($str));
	}

	// --------------------------------------------------------------------

	/**
	 * Max Length
	 *
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	public function max_length($str, $val)
	{
		if ( ! is_numeric($val))
		{
			return FALSE;
		}

		return ($val >= mb_strlen($str));
	}

	// --------------------------------------------------------------------

	/**
	 * Exact Length
	 *
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	public function exact_length($str, $val)
	{
		if ( ! is_numeric($val))
		{
			return FALSE;
		}

		return (mb_strlen($str) === (int) $val);
	}

	// --------------------------------------------------------------------

	/**
	 * Valid URL
	 *
	 * @param	string	$str
	 * @return	bool
	 */
	public function valid_url($str)
	{
		if (empty($str))
		{
			return FALSE;
		}
		elseif (preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches))
		{
			if (empty($matches[2]))
			{
				return FALSE;
			}
			elseif ( ! in_array(strtolower($matches[1]), array('http', 'https'), TRUE))
			{
				return FALSE;
			}

			$str = $matches[2];
		}

		// PHP 7 accepts IPv6 addresses within square brackets as hostnames,
		// but it appears that the PR that came in with https://bugs.php.net/bug.php?id=68039
		// was never merged into a PHP 5 branch ... https://3v4l.org/8PsSN
		if (preg_match('/^\[([^\]]+)\]/', $str, $matches) && ! is_php('7') && filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== FALSE)
		{
			$str = 'ipv6.host'.substr($str, strlen($matches[1]) + 2);
		}

		return (filter_var('http://'.$str, FILTER_VALIDATE_URL) !== FALSE);
	}

	// --------------------------------------------------------------------

	/**
	 * Valid Email
	 *
	 * @param	string
	 * @return	bool
	 */
	public function valid_email($str)
	{
		if (function_exists('idn_to_ascii') && preg_match('#\A([^@]+)@(.+)\z#', $str, $matches))
		{
			$domain = defined('INTL_IDNA_VARIANT_UTS46')
				? idn_to_ascii($matches[2], 0, INTL_IDNA_VARIANT_UTS46)
				: idn_to_ascii($matches[2]);

			if ($domain !== FALSE)
			{
				$str = $matches[1].'@'.$domain;
			}
		}

		return (bool) filter_var($str, FILTER_VALIDATE_EMAIL);
	}

	// --------------------------------------------------------------------

	/**
	 * Valid Emails
	 *
	 * @param	string
	 * @return	bool
	 */
	public function valid_emails($str)
	{
		if (strpos($str, ',') === FALSE)
		{
			return $this->valid_email(trim($str));
		}

		foreach (explode(',', $str) as $email)
		{
			if (trim($email) !== '' && $this->valid_email(trim($email)) === FALSE)
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate IP Address
	 *
	 * @param	string
	 * @param	string	'ipv4' or 'ipv6' to validate a specific IP format
	 * @return	bool
	 */
	public function valid_ip($ip, $which = '')
	{
		return $this->CI->input->valid_ip($ip, $which);
	}

	// --------------------------------------------------------------------

	/**
	 * Alpha
	 *
	 * @param	string
	 * @return	bool
	 */
	public function alpha($str)
	{
		return ctype_alpha($str);
	}

	// --------------------------------------------------------------------

	/**
	 * Alpha-numeric
	 *
	 * @param	string
	 * @return	bool
	 */
	public function alpha_numeric($str)
	{
		return ctype_alnum((string) $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Alpha-numeric w/ spaces
	 *
	 * @param	string
	 * @return	bool
	 */
	public function alpha_numeric_spaces($str)
	{
		return (bool) preg_match('/^[A-Z0-9 ]+$/i', $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Alpha-numeric with underscores and dashes
	 *
	 * @param	string
	 * @return	bool
	 */
	public function alpha_dash($str)
	{
		return (bool) preg_match('/^[a-z0-9_-]+$/i', $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Numeric
	 *
	 * @param	string
	 * @return	bool
	 */
	public function numeric($str)
	{
		return (bool) preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $str);

	}

	// --------------------------------------------------------------------

	/**
	 * Integer
	 *
	 * @param	string
	 * @return	bool
	 */
	public function integer($str)
	{
		return (bool) preg_match('/^[\-+]?[0-9]+$/', $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Decimal number
	 *
	 * @param	string
	 * @return	bool
	 */
	public function decimal($str)
	{
		return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Greater than
	 *
	 * @param	string
	 * @param	int
	 * @return	bool
	 */
	public function greater_than($str, $min)
	{
		return is_numeric($str) ? ($str > $min) : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Equal to or Greater than
	 *
	 * @param	string
	 * @param	int
	 * @return	bool
	 */
	public function greater_than_equal_to($str, $min)
	{
		return is_numeric($str) ? ($str >= $min) : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Less than
	 *
	 * @param	string
	 * @param	int
	 * @return	bool
	 */
	public function less_than($str, $max)
	{
		return is_numeric($str) ? ($str < $max) : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Equal to or Less than
	 *
	 * @param	string
	 * @param	int
	 * @return	bool
	 */
	public function less_than_equal_to($str, $max)
	{
		return is_numeric($str) ? ($str <= $max) : FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Value should be within an array of values
	 * Example: in_list[red,blue,green]
	 *
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	public function in_list($value, $list)
	{
		return in_array($value, explode(',', $list), TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Is a Natural number  (0,1,2,3, etc.)
	 *
	 * @param	string
	 * @return	bool
	 */
	public function is_natural($str)
	{
		return ctype_digit((string) $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Is a Natural number, but not a zero  (1,2,3, etc.)
	 *
	 * @param	string
	 * @return	bool
	 */
	public function is_natural_no_zero($str)
	{
		return ($str != 0 && ctype_digit((string) $str));
	}

	// --------------------------------------------------------------------

	/**
	 * Valid Base64
	 *
	 * Tests a string for characters outside of the Base64 alphabet
	 * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
	 *
	 * @param	string
	 * @return	bool
	 */
	public function valid_base64($str)
	{
		return (base64_encode(base64_decode($str)) === $str);
	}


}

/* End of file Validation.php */
