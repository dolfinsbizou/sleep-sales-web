<?php
/*
 * This file is part of the Astaroth package.
 *
 * (c) 2016 Victorien POTTIAU ~ Emmanuel LEROUX
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Astaroth\Helpers;

use Astaroth;
use AstarothException;

class FilterHelper
{
    /**
     * Filters data using PHP's filter extension
     *
     * @see filter_var()
     * @param mixed $data
     * @param mixed $filter
     * @param mixed $options
     * @param bool $falseOnFail
     * @return mixed
     * @throws AstarothException
     */
    function filter($data, $filter = null, $options = null, $falseOnFail = true)
    {
        if (is_array($data)) {
            // the $filter must be an array or a string to an array defined under app.filters.rules
            if (is_string($filter)) {
                if (($rules = Astaroth::get("helpers.filters.rules.$filter", false)) === false) {
                    throw new AstarothException('When $data is an array, the filter must be an array of definition or a defination name in filter()');
                }
            } else {
                $rules = $filter;
            }
            
            $results = array();
            $messages = array();
            $validate = true;
            
            foreach ($rules as $field => $params) {
                if (isset($data[$field]) && is_array($data[$field])) {
                    if (($results[$field] = $this->filter($data[$field], $params)) === false) {
                        $messages[$field] = Astaroth::get('helpers.filters.messages', array());
                        $validate = false;
                    }
                    continue;
                }
                
                $filter = FILTER_SANITIZE_STRING;
                $message = Astaroth::get('helpers.filters.default_message', 'The %s field failed to validate');
                $required = false;
                $default = null;
                $label = $field;
                if (is_array($params)) {
                    // extracting information from the array
                    if (isset($params['message'])) {
                        $message = Astaroth::delete('message', $params);
                    }
                    if (isset($params['required'])) {
                        $required = Astaroth::delete('required', $params);
                    }
                    if (isset($params['default'])) {
                        $default = Astaroth::delete('default', $params);
                    }
                    if (isset($params['label'])) {
                        $label = Astaroth::delete('label', $params);
                    }
                    if (isset($params['filter'])) {
                        $filter = Astaroth::delete('filter', $params);
                    }
                    $options = count($params) == 0 ? null : $params;
                } else {
                    $filter = $params;
                    $options = null;
                }
                
                if (!isset($data[$field]) && !$required) {
                    // field not set and not required, do nothing
                    continue;
                }
                
                if ((!isset($data[$field]) || $data[$field] == '') && $required) {
                    // the field is required and either not set or empty, this is an error
                    $results[$field] = false;
                    $message = Astaroth::get('helpers.filters.required_message', 'The %s field must be filled');
                    
                } else if ($data[$field] === '' && !$required) {
                    // empty but not required, null value
                    $results[$field] = $default;
                    
                } else {
                    // normal, validating
                    $results[$field] = $this->filter($data[$field], $filter, $options);
                }
                
                if ($results[$field] === false) {
                    // failed validation, adding the message
                    $messages[$field] = sprintf($message, $label);
                    $validate = false;
                }
            }

            Astaroth::set('helpers.filters.messages', $messages);
            return $validate || !$falseOnFail ? $results : false;
        }
        
        if (is_string($filter)) {
            if (in_array($filter, filter_list())) {
                // filter name from the extension filters
                $filter = filter_id($filter);
                
            } else if (preg_match('@/.+/[a-zA-Z]*@', $filter)) {
                // regexp
                $options = array('options' => array('regexp' => $filter));
                $filter = FILTER_VALIDATE_REGEXP;
                
            } else if (($callback = Astaroth::get("helpers.filters.callbacks.$filter", false)) !== false) {
                // callback defined under app/filters/callbacks
                $filter = FILTER_CALLBACK;
                $options = $callback;
            } 
        }
        
        return filter_var($data, $filter, $options);
    }
}
