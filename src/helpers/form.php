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

class FormHelper
{
    const ENCTYPE_FORMDATA = 'multipart/form-data';
    const ENCTYPE_URLENCODED = 'application/x-www-form-urlencoded';
    
    public $action;
    
    public $name;
    
    public $attrs = array();
    
    public function form($action = '', $name = null, $attrs = array())
    {
        $this->action = $action;
        $this->name = $name;
        $this->attrs = $attrs;
        return $this;
    }
    
    public function open($action = '', $name = null, $attrs = array())
    {
        $attrs = array_merge($attrs, array(
            'action' => $action,
            'method' => Astaroth::get('method', 'POST', $attrs),
            'class' => Astaroth::get('class', Astaroth::get('helpers.form.default_form_class', ''), $attrs)
        ));
        
        if ($name !== null) {
            $attrs['name'] = $name;
            $attrs['id'] = $name;
        }
        
        return '<form ' . Astaroth::htmlAttributes($attrs) . '>';
    }
    
    public function __toString()
    {
        return $this->open($this->action, $this->name, $this->attrs);
    }
    
    public function label($label, $required = false, $attrs = array())
    {
        return sprintf('<label %s>%s %s</label>',
            Astaroth::htmlAttributes($attrs),
            $label,
            $required ? '<span class="required">*</span>' : ''
        );
    }
    
    public function input($name, $value = '', $type = 'text', $attrs = array())
    {
        $attrs = array_merge($attrs, array(
            'name' => $name,
            'type' => $type,
            'value' => Astaroth::get($name, $value, $_POST),
            'class' => Astaroth::get('class', Astaroth::get('helpers.form.default_input_class', ''), $attrs)
        ));
        
        return sprintf('<input %s />', Astaroth::htmlAttributes($attrs));
    }

    public function password($name, $attrs = array())
    {
        return $this->input($name, '', 'password', $attrs);
    }

    public function email($name, $value = '', $attrs = array())
    {
        return $this->input($name, $value, 'email', $attrs);
    }

    public function date($name, $value = '', $attrs = array())
    {
        return $this->input($name, $value, 'date', $attrs);
    }

    public function time($name, $value = '', $attrs = array())
    {
        return $this->input($name, $value, 'time', $attrs);
    }

    public function number($name, $value = '', $attrs = array())
    {
        return $this->input($name, $value, 'number', $attrs);
    }

    public function url($name, $value = '', $attrs = array())
    {
        return $this->input($name, $value, 'url', $attrs);
    }

    public function datetime($name, $value = '', $attrs = array())
    {
        return $this->input($name, $value, 'datetime', $attrs);
    }
    
    public function checkbox($name, $checked = false, $value = 1, $attrs = array())
    {
        if ($checked) {
            $attrs['checked'] = 'checked';
        }
        return $this->input($name, $value, 'checkbox', $attrs);
    }
    
    public function file($name, $attrs = array())
    {
        return $this->input($name, '', 'file', $attrs);
    }
    
    public function hidden($name, $value = '', $attrs = array())
    {
        return $this->input($name, $value, 'hidden', $attrs);
    }
    
    public function select($name, $options, $value = '', $attrs = array())
    {
        $attrs = array_merge($attrs, array(
            'name' => $name,
            'class' => Astaroth::get('class', Astaroth::get('helpers.form.default_select_class', ''), $attrs)
        ));
        
        $html = sprintf('<select %s>', Astaroth::htmlAttributes($attrs));
        foreach ($options as $key => $text) {
            $html .= sprintf('<option value="%s"%s>%s</option>', 
                $key, 
                $key == $value ? ' selected="selected"' : '',
                Astaroth::escape($text)
            );
        }
        $html .= '</select>';
        
        return $html;
    }
    
    public function textarea($name, $value = '', $attrs = array())
    {
        $attrs = array_merge($attrs, array(
            'name' => $name,
            'class' => Astaroth::get('class', Astaroth::get('helpers.form.default_textarea_class', ''), $attrs)
        ));
        
        return sprintf('<textarea %s>%s</textarea>',
            Astaroth::htmlAttributes($attrs),
            Astaroth::get($name, $value, $_POST));
    }
    
    public function button($text, $type = 'submit', $attrs = array())
    {
        $attrs = array_merge($attrs, array(
            'type' => $type,
            'class' => Astaroth::get('class', Astaroth::get('helpers.form.default_button_class', ''), $attrs)
        ));
        
        return sprintf('<button %s>%s</button>', Astaroth::htmlAttributes($attrs), $text);
    }
    
    public function buttons($submitText = 'Submit', $cancelUrl = 'javascript:history.back()', $buttonAttrs = array(), $cancelText = 'or <a href="%s">cancel</a>')
    {
        $html = $this->button($submitText, 'submit', $buttonAttrs);
        
        if ($cancelUrl !== false) {
            $html .= sprintf($cancelText, $cancelUrl);
        }
        
        return $html;
    }
    
}
