<?php
/*
 * This file is part of the Astaroth package.
 *
 * (c) 2016 Victorien POTTIAU ~ Emmanuel LEROUX
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Astaroth;

use Astaroth;
use AstarothException;

class Flash implements  iPlugin, \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * Starts this class as a plugin
     */
    public static function start(&$config)
    {
        Astaroth::registerHelper('flash', 'Astaroth\Flash::flash');
        Astaroth::registerHelper('flashMessages', 'Astaroth\Flash::renderFlashMessages');
        Astaroth::set('flash_messages', new Flash());
    }

    /**
     * Saves a message that can be retrieve only once
     *
     * @param string|array $message One message as a string or many messages as an array
     * @param string $label
     * @throws AstarothException
     */
    public static function flash($message, $label = 'default')
    {
        if (!isset($_SESSION)) {
            throw new AstarothException('The session must be started before using Astaroth::flash()');
        }

        Astaroth::fireEvent('Astaroth::Flash', array(&$message, &$label));
        
        if (!Astaroth::has("session.__FLASH.$label")) {
            Astaroth::set("session.__FLASH.$label", array());
        }
        Astaroth::add("session.__FLASH.$label", $message);
    }
    
    /**
     * Returns the flash messages saved in the session
     * 
     * @internal 
     * @param string $label Whether to only retreives messages from this label. When null or 'all', returns all messages
     * @param bool $delete Whether to delete messages once retrieved
     * @return array An array of messages if the label is specified or an array of array message
     */
    public static function getFlashMessages($label = null, $delete = true) {
        if (!Astaroth::has('session.__FLASH')) {
            return array();
        }
        
        if ($label === null) {
        	if ($delete) {
            	return Astaroth::delete('session.__FLASH');
        	}
        	return Astaroth::get('session.__FLASH');
        }
        
        if (!Astaroth::has("session.__FLASH.$label")) {
            return array();
        }
        
        if ($delete) {
        	return Astaroth::delete("session.__FLASH.$label");
        }
        return Astaroth::get("session.__FLASH.$label");
    }
    
    /**
     * Renders the messages as html
     *
     * @param string $id The wrapping ul's id
     * @return string
     */
    public static function renderFlashMessages($id = 'flash-messages')
    {
        $html = '';
    	foreach (self::getFlashMessages() as $label => $messages) {
    	    foreach ($messages as $message) {
    	        $html .= sprintf('<li class="%s">%s</li>', $label, $message);
    	    }
    	}
    	if (empty($html)) {
    	    return '';
    	}
    	return '<ul id="' . $id . '">' . $html . '</ul>';
    }
    
    public function __construct()
    {
        
    }

    public function getIterator()
    {
        return new \ArrayIterator(self::getFlashMessages());
    }

    public function count()
    {
        $i = 0;
        foreach (self::getFlashMessages(null, false) as $label => $msg) {
            $i += count($msg);
        }
        return $i;
    }

    public function offsetGet($label)
    {
        return self::getFlashMessages($label);
    }

    public function offsetSet($label, $value)
    {

    }

    public function offsetExists($label)
    {
        return Astaroth::has("session.__FLASH.$label");
    }

    public function offsetUnset($label)
    {
        self::getFlashMessages($label);
    }
}

