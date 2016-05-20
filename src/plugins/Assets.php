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

use Assetic\Asset\AssetCollection;
use Astaroth;
use ConsoleKit\FileSystem;
use ConsoleKit\Widgets\Checklist;

class Assets implements iPlugin
{
    public static $config = array();

    public static $loadedAssets = array();

    public static function start($config)
    {
        self::$config = array_merge(array(

            'packages' => array(),

            'assets_dir' => 'app/assets',

            'public_assets_dir' => 'assets',

            'allow_file_assets' => true,

            'css_filters' => array(),

            'css_extension' => 'css',

            'js_filters' => array(),

            'js_extension' => 'js'

        ), $config);

        Astaroth::registerHelper('loadAsset', 'Astaroth\Assets::load');
        Astaroth::registerHelper('renderAssets', 'Astaroth\Assets::render');

        if (Astaroth::isPluginLoaded('Console')) {
            Console::register('write-assets', 'Astaroth\Assets::write');
        }
    }

    public static function load($filename, $type = null)
    {
        if ($type === null) {
            if (strtolower(substr($filename, -4)) === '.css') {
                $type = 'css';
            } else {
                $type = 'js';
            }
        }
        self::$loadedAssets[$filename] = array($filename, $type);
    }

    public static function render($type = null)
    {
        $output = array();
        foreach (array_reverse(self::$loadedAssets) as $asset) {
            list($filename, $assetType) = $asset;
            if ($type !== null && $assetType !== $type) {
                continue;
            }
            $url = Astaroth::asset(Astaroth::path($filename, self::$config['public_assets_dir'], false, '/'));
            if ($type === 'css') {
                $output[] = sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $url);
            } else if ($type === 'js') {
                $output[] = sprintf('<script type="text/javascript" src="%s"></script>', $url);
            }
        }
        $output = implode("\n", $output);
        Astaroth::fireEvent('Assets::render', array(&$output, $type));
        return $output;
    }

    public static function write($args, $opts, $console)
    {
        $publicDir = Astaroth::path(self::$config['public_assets_dir'], Astaroth::get('astaroth.dirs.public'), false);
        $checklist = new Checklist($console);

        $console->writeln("Writing packages to '$publicDir'");
        foreach (array_keys(self::$config['packages']) as $name) {
            $filename = Astaroth::path($name, $publicDir, false);
            $checklist->step($filename, function() use ($filename, $name) {
                return FileSystem::touch($filename, self::dump($name));
            });
        }

        if (self::$config['allow_file_assets']) {
            $dir = Astaroth::path(self::$config['assets_dir']);
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            $console->writeln("Writing files to '$publicDir'");
            foreach ($it as $file) {
                if ($file->isDir() || substr($file->getFilename(), 0, 1) === '.') {
                    continue;
                }
                $filename = trim(substr($file->getPathname(), strlen($dir)), DIRECTORY_SEPARATOR);
                $pathname = Astaroth::path(substr($filename, 0, strrpos($filename, '.')), $publicDir, false);
                if (in_array($file->getExtension(), (array) self::$config['css_extension'])) {
                    $pathname .= '.css';
                } else if (in_array($file->getExtension(), (array) self::$config['js_extension'])) {
                    $pathname .= '.js';
                } else {
                    $pathname .= '.' . $file->getExtension();
                }
                FileSystem::touch($pathname, self::dump($filename));
            }
        }

        Astaroth::fireEvent('Assets::write');
    }

    public static function getPackageFiles($package)
    {
        $files = array();
        foreach ((array) self::$config['packages'][$package] as $file) {
            if ($file{0} === '@') {
                $files = array_merge($files, self::getPackageFiles(substr($file, 1)));
            } else {
                $files[] = $file;
            }
        }
        return $files;
    }

    public static function getFiltersForFile($filename)
    {
        $extension = strtolower(substr($filename, strrpos($filename, '.') + 1));
        $filters = array();
        if (in_array($extension, (array) self::$config['css_extension'])) {
            $filters = self::$config['css_filters'];
        } else if (in_array($extension, (array) self::$config['js_extension'])) {
            $filters = self::$config['js_filters'];
        }
        return array_map('Astaroth\Assets::createFilter', $filters);
    }

    public static function createFilter($filter)
    {
        if (is_string($filter)) {
            $filter = new $filter();
        } else if (is_array($filter)) {
            $classname = array_shift($filter);
            $class = new \ReflectionClass($classname);
            $filter = $class->newInstanceArgs($filter);
        }
        return $filter;
    }

    public static function dump($filename)
    {
        if (isset(self::$config['packages'][$filename])) {
            $files = self::getPackageFiles($filename);
        } else {
            $files = array($filename);
        }

        $assetsDir = Astaroth::path(self::$config['assets_dir']);
        $assets = array();
        foreach ($files as $file) {
            $filters = self::getFiltersForFile($file);
            if ($file{0} === '!') {
                $file = substr($file, 1);
                $filters = array();
            }
            $className = 'Assetic\Asset\FileAsset';
            if (preg_match('/^[a-z]+:\/\//', $file)) {
                $className = 'Assetic\Asset\HttpAsset';
            } else {
                if (strpos($file, '*') !== false) {
                    $className = 'Assetic\Asset\GlobAsset';
                }
                $file = Astaroth::path($file, $assetsDir, false);
            }
            $assets[] = new $className($file, $filters);
        }

        $collection = new AssetCollection($assets);
        Astaroth::fireEvent('Assets::dump', array($collection));
        return $collection->dump();
    }

    public static function serve($filename)
    {
        $extension = strtolower(substr($filename, strrpos($filename, '.') + 1));
        $exists = isset(self::$config['packages'][$filename]);

        if (!$exists && self::$config['allow_file_assets']) {
            $pathname = Astaroth::findFile($filename, Astaroth::path(self::$config['assets_dir']));
            if ($pathname) {
                $exists = true;
            }
        }

        Astaroth::fireEvent('Assets::serve', array(&$filename, &$exists));

        if (!$exists) {
            Astaroth::trigger404();
        }
        if ($extension === 'css' || in_array($extension, (array) self::$config['css_extension'])) {
            header('Content-type: text/css');
        } else if ($extension === 'js' || in_array($extension, (array) self::$config['js_extension'])) {
            header('Content-type: text/javascript');
        }
        echo self::dump($filename);
    }

    public static function onAstarothDispatchUri($uri, $request, &$cancel)
    {
        $pattern = self::$config['public_assets_dir'] . '/*';
        if (!Astaroth::uriMatch($pattern, $uri)) {
            return;
        }
        $uri = trim(substr(trim($uri, '/'), strlen(self::$config['public_assets_dir'])), '/');
        self::serve($uri);
        Astaroth::end(true);
    }
}
