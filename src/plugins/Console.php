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
use ConsoleKit;
use ConsoleKit\FileSystem;
use ConsoleKit\Widgets\Checklist;

class Console implements iPlugin
{
    /** @var array */
    public static $config = array();
    
    /** @var array */
    private static $commands = array();

    /** @var ConsoleKit\Console */
    public static $console;
    
    /**
     * Checks we're in console mode
     *
     * @param array $config
     * @return bool
     */
    public static function start(&$config)
    {
        $config = array_merge(array(
        
            // directory where scripts are stored
            'scripts_dir'    => 'app/scripts'
            
        ), $config);
        self::$config = &$config;

        self::$console = new ConsoleKit\Console();
        self::$console->addCommand('Astaroth\Console::generate');
        
        // checks if we are in the CLI
        if (PHP_SAPI !== 'cli') {
            return false;
        }
        return true;
    }
    
    /**
     * Display the console and execute callbacks associated
     * to the command
     */
    public static function onAstarothStart()
    {
        $paths = (array) self::$config['scripts_dir'];
        foreach (Astaroth::getLoadedPlugins(true) as $plugin => $path) {
            $paths[] = "$path/scripts";
        }

        foreach (array_filter(array_map('realpath', $paths)) as $path) {
            self::$console->addCommandsFromDir($path, '', true);
        }

        self::$console->run();
        exit();
    }
    
    /**
     * Registers a callback to call when a command is
     * executed
     *
     * @param string $command
     * @param callback $callback
     */
    public static function register($command, $callback)
    {
        self::$console->addCommand($callback, $command);
    }

    /**
     * Generate command.
     * index.php generate action_name [action_name [action_name [...]]]
     *
     * @param $args
     * @param $opts
     * @param $console
     * @internal param array $arguments
     */
    public static function generate($args, $opts, $console)
    {
        $checklist = new Checklist($console);
        foreach ($args as $action) {
            $console->writeln("Generating '$action'");
            $filename = ltrim($action, '/') . '.php';

            $actionsDir = (array) Astaroth::get('astaroth.dirs.actions');
            $actionsDir = array_shift($actionsDir);
            $checklist->step("Creating action file in $actionsDir", function() use ($actionsDir, $filename) {
                return FileSystem::touch(FileSystem::join($actionsDir, $filename),
                    "<?php\n\n// Logic goes here\n");
            });
        
            $viewsDir = (array) Astaroth::get('astaroth.dirs.views');
            $viewsDir = array_shift($viewsDir);
            $checklist->step("Creating view file in $viewsDir", function() use ($viewsDir, $filename) {
                return FileSystem::touch(FileSystem::join($viewsDir, $filename));
            });

            Astaroth::fireEvent('Console::Generate', array($action));
        }
    }
}
