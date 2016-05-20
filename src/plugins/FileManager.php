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

final class FileManager implements iPlugins {

    private static $config = [];

    private static $ext = ['pdf'];

    public static function start(&$config) {
        $config = array_merge([
                'uploadDir' => '/var/www',
                'maxSize' => 5000,
            ], $config
        );
    }

    public static function upload($p_index, $p_dest) {
        if (!isset($_FILES[$p_index]) || $_FILES[$p_index]['error'] > 0)
            return false;
        if (!is_uploaded_file($_FILES[$p_index]['tmp_name']))
            return false;
        if (!$_FILES[$p_index] > self::$config['maxSize'])
            return false;
        $v_ext = substr(strrchr($_FILES[$p_index]['name'], '.'), 1);
        if (!in_array($v_ext, self::$ext))
            return false;
        $v_dest = self::$config['uploadDir'] . $p_dest . '.' . $v_ext;
        if(!move_uploaded_file($_FILES[$p_index]['tmp_name'], $v_dest))
            return false;
        $v_result = $_FILES[$p_index];
        $v_result['token'] = "sd";
        return $v_result;
    }

    public static function exist($p_filename) {
        return true;
    }

    public static function download($p_filename, $p_filesize) {
        header('Content-Transfer-Encoding: binary');
        header('Content-Disposition: attachment; filename=' . $p_filename);
        header('Content-Length: ' . $p_filesize);
        readfile($p_filename);
    }
}