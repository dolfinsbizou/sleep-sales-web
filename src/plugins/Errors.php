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
use AstarothHttpException;
use Exception;
use AstarothException;

class Errors implements iPlugin
{
    /** @var array */
    public static $config = array();

    /**
     * Starts this class as a plugin
     *
     * @param array $config
     * @return bool
     */
    public static function start(&$config)
    {
        $config = array_merge(array(
        
            /* @var bool */
            'catch_errors'           => false,
            
            /* @var bool */
            'throw_errors'           => false,
            
            /* Which view to render when an error occurs
             * @var string */
            'error_view'             => 'errors/error',
            
            /* Which view to render when an error occurs
             * @var string */
            '404_redirect'           => null,
            '404_view'               => 'errors/404',
            
            /* @var array */
            'error_report_attrs'     => array(
                'astaroth-error'               => 'style="padding: 10px"',
                'astaroth-error-title'         => 'style="font-size: 1.3em; font-weight: bold; color: #FF0000"',
                'astaroth-error-lines'         => 'style="width: 100%; margin-bottom: 20px; background-color: #fff;'
                                              . 'border: 1px solid #000; font-size: 0.8em"',
                'astaroth-error-line'          => '',
                'astaroth-error-line-error'    => 'style="background-color: #ffe8e7"',
                'astaroth-error-line-number'   => 'style="background-color: #eeeeee"',
                'astaroth-error-line-text'     => '',
                'astaroth-error-stack'         => ''
            )
            
        ), $config);
        self::$config = &$config;

        Astaroth::registerHelper('renderException', 'Astaroth\Errors::render');
        
        // checks if we are in the CLI
        if (PHP_SAPI === 'cli') {
            return false;
        }
        return true;
    }
    
    public static function onAstarothError($e, &$cancel)
    {
        $cancel = true;
        self::handle($e);
        Astaroth::end(false);
    }
    
    public static function onAstarothHttperror($e, &$cancel)
    {
        $cancel = true;
        self::handle($e);
        Astaroth::end(false);
    }

    /**
     * Handles an exception according to the config
     *
     * @param Exception $e
     * @throws Exception
     */
    public static function handle(Exception $e)
    {
        if ($e instanceof AstarothHttpException) {
            header('Location: ', false, $e->getCode());
            if ($e->getCode() === 404) {
                if(self::$config['404_redirect'])
                    //return Astaroth::redirect(Astaroth::url(self::$config['404_redirect']));
                header('Content-type: text/html');
                if ($output = Astaroth::render(self::$config['404_view'])) {
                    echo $output;
                } else {
                    echo '<h1>Page not found</h1>';
                }
            }
        } else {
            header('Location: ', false, 500);
            if (self::$config['catch_errors']) {
                if ($output = Astaroth::render(self::$config['error_view'])) {
                    echo $output;
                } else {
                    echo self::render($e);
                }
            } else if (self::$config['throw_errors']) {
                throw $e;
            }
        }
    }
    
    /**
     * Renders an exception
     * 
     * @param Exception $exception The exception which sould ne rendered
     * @param bool $return Return the output instead of printing it
     * @return string
     */
    public static function render(Exception $exception, $return = false)
    {
        $attributes = self::$config['error_report_attrs'];

        $html = '<div ' . $attributes['astaroth-error'] . '>'
            . '<span ' . $attributes['astaroth-error-title'] . '>'
            . $exception->getMessage() . '</span>'
            . '<br />An error of type <strong>' . get_class($exception) . '</strong> '
            . 'was caught at <strong>line ' . $exception->getLine() . '</strong><br />'
            . 'in file <strong>' . $exception->getFile() . '</strong>'
            . '<p>' . $exception->getMessage() . '</p>'
            . '<table ' . $attributes['astaroth-error-lines'] . '>';

        // builds the table which display the lines around the error
        $lines = file($exception->getFile());
        $start = $exception->getLine() - 7;
        $start = $start < 0 ? 0 : $start;
        $end = $exception->getLine() + 7;
        $end = $end > count($lines) ? count($lines) : $end;
        for ($i = $start; $i < $end; $i++) {
            // color the line with the error. with standard Exception, lines are
            if ($i == $exception->getLine() - (get_class($exception) != 'ErrorException' ? 1 : 0)) {
                $html .= '<tr ' . $attributes['astaroth-error-line-error'] . '><td>';
            } else {
                $html .= '<tr ' . $attributes['astaroth-error-line'] . '>'
                    . '<td ' . $attributes['astaroth-error-line-number'] . '>';
            }
            $html .= $i . '</td><td ' . $attributes['astaroth-error-line-text'] . '>'
                . (isset($lines[$i]) ? htmlspecialchars($lines[$i]) : '') . '</td></tr>';
        }

        $index = 0;
        $lines = false;
        do {
            if (isset($exception->getTrace()[$index]['file']))
                $lines = file($exception->getTrace()[$index]['file']);
            $index++;
        } while ($lines === false && $index < count($exception->getTrace()));

        if ($lines !== false) {
            $index--;
            $html .= '</table><table ' . $attributes['astaroth-error-lines'] . '>';
            $lines = file($exception->getTrace()[$index]['file']);
            $start = $exception->getTrace()[$index]['line'] - 7;
            $start = $start < 0 ? 0 : $start;
            $end = $exception->getTrace()[$index]['line'] + 7;
            $end = $end > count($lines) ? count($lines) : $end;
            for ($i = $start; $i < $end; $i++) {
                if ($i == $exception->getTrace()[$index]['line'] - 1) {
                    $html .= '<tr ' . $attributes['astaroth-error-line-error'] . '><td>';
                } else {
                    $html .= '<tr ' . $attributes['astaroth-error-line'] . '>'
                        . '<td ' . $attributes['astaroth-error-line-number'] . '>';
                }
                $html .= $i . '</td><td ' . $attributes['astaroth-error-line-text'] . '>'
                    . (isset($lines[$i]) ? htmlspecialchars($lines[$i]) : '') . '</td></tr>';
            }
        }
        $html .= '</table>'
               . '<strong>Stack:</strong><p ' . $attributes['astaroth-error-stack'] . '>'
               . nl2br($exception->getTraceAsString())
               . '</p></div>';
        
        return $html;
    }
}
