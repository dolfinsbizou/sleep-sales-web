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
use DirectoryIterator;

class Translations implements iPlugin
{
    /** @var array */
    public static $config = array();
    
    /** @var array */
    protected static $messages = array();
    
    /**
     * Plugin starts
     *
     * @param array $config
     */
    public static function start(&$config)
    {
        $config = array_merge(array(
        
            /* default language */
            'language'      => 'en',

            /* GET param to override the language */
            'lang_param'    => 'lang',
            
            /* autodetect browser language */
            'autodetect'    => true,
            
            /* directory where language files are stored */
            'dir'           => 'app/languages'
    
        ), $config);
        self::$config = &$config;

        Astaroth::registerHelper('translate', 'Astaroth\Translations::translate');
        Astaroth::registerHelper('_', 'Astaroth\Translations::translate');
    }
    
    /**
     * Sets the language
     */
    public static function onAstarothStart()
    {
        // override from the url
        $param = self::$config['lang_param'];
        if ($param && isset($_GET[$param]) && self::exists($_GET[$param])) {
            self::set($_GET[$param]);
            return;
        }
        
        // language already discovered
        if (isset($_SESSION) && isset($_SESSION['__LANG']) && self::exists($_SESSION['__LANG'])) {
            self::set($_SESSION['__LANG']);
            return;
        }
        
        // autodetects language using HTTP_ACCEPT_LANGUAGE
        // Language tag: primaryLang-subLang;q=?
        if (self::$config['autodetect'] === true && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $acceptLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($acceptLanguages as $language) {
                // checks if sublang is supported
                $subLang = explode(';', $language);
                if (self::exists($subLang[0])) {
                    self::set($subLang[0]);
                    return;
                }
                // checks if primary lang is supported
                $primaryLang = explode('-', $subLang[0]);
                if (self::exists($primaryLang[0])) {
                    self::set($primaryLang[0]);
                    return;
                }
            }
        }
        
        // uses default language
        if (self::exists($lang = self::$config['language'])) {
            self::set($lang);
        }
    }
    
    /**
     * Checks if a language is supported
     *
     * @param string $language
     * @param string|array $dirs OPTIONAL Directories where language files are stored
     * @return bool
     */
    public static function exists($language, $dirs = null)
    {
        return Astaroth::findFile("$language.php", $dirs ?: self::$config['dir']) !== false;
    }
    
    /**
     * Gets defined languages
     * 
     * @param string|array $dirs OPTIONAL Directories where language files are stored
     * @return array
     */
    public static function getDefinedLanguages($dirs = null)
    {
        $dirs = $dirs ?: self::$config['dir'];
        $languages = array();
        foreach (array_filter(Astaroth::path((array) $dirs)) as $dir) {
            if (is_dir($dir)) {
                foreach (new DirectoryIterator($dir) as $file) {
                    $filename = $file->getFilename();
                    if ($filename{0} == '.' || $file->isDir()) {
                        continue;
                    }
                    $languages[] = substr($filename, 0, strrpos($filename, '.'));
                }
            }
        }
        return $languages;
    }

    /**
     * Sets the language to use
     *
     * @param string $language OPTIONAL (default null) Null to use default language
     * @throws AstarothException
     */
    public static function set($language = null)
    {
        self::$messages = array();
        $language = $language ?: self::$config['language'];
        $filename = Astaroth::findFile("$language.php", Astaroth::path(self::$config['dir']));
        if ($filename === false) {
            throw new AstarothException("Language '$language' does not exists");
        }

        /** @noinspection PhpIncludeInspection */
        $messages = include($filename);
        if (is_array($messages)) {
            self::$messages = array_merge(self::$messages, $messages);
        }

        Astaroth::set('app.language', $language);
        self::$config['language'] = $language;
        if (isset($_SESSION)) {
            $_SESSION['__LANG'] = $language;
        }
    }
    
    /**
     * Gets the current language
     *
     * @return string
     */
    public static function get()
    {
        return self::$config['language'];
    }
    
    /**
     * Sets messages
     * Must be used in language file
     *
     * @param array $messages
     */
    public static function setMessages($messages)
    {
        self::$messages = array_merge(self::$messages, $messages);
    }
    
    /**
     * Translate a text. Works the same way as sprintf.
     *
     * @param string $text
     * @return string
     */
    public static function translate($text)
    {
        $args = func_get_args();
        unset($args[0]);
        
        if (isset(self::$messages[$text])) {
            $text = self::$messages[$text];
        }
        
        return vsprintf($text, $args);
    }
}

// registers the __ functions if possible
if (!function_exists('__')) {
    /**
     * Translate a text. Works the same way as sprintf.
     *
     * @see LangPlugin::_()
     * @param string $text
     * @return string
     */
    function __($text)
    {
        return call_user_func_array('Astaroth\Translations::translate', func_get_args());
    }
}
