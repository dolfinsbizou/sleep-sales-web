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

/**
 * Class Cron
 * @package Astaroth
 */
final class Cron implements iPlugin {

    /**
     * @var string
     */
    private static $start = '#The following lines are administered automatically via a PHP script. - Please do not to edit manually';

    /**
     * @var string
     */
    private static $end = '#The following lines are no longer handled automatically';

    /**
     * @param $config
     */
    public static function start(&$config) {
        // TODO: Implement start() method.
    }

    /**
     * @param $p_command
     * @param $p_commentary
     * @param string $p_hour
     * @param string $p_minute
     * @param string $p_dayMonth
     * @param string $p_dayWeek
     * @param string $p_month
     * @return bool|int
     */
    public static function add($p_command, $p_commentary, $p_hour = '*', $p_minute = '*', $p_dayMonth = '*', $p_dayWeek = '*', $p_month = '*') {
        $v_oldCrontab = [];
        $v_newCrontab = [];
        $v_isSection = false;
        $v_maxId = 0;
        $v_newId = 0;

        exec('crontab -l', $v_oldCrontab);

        foreach ($v_oldCrontab as $index => $line) {
            if ($v_isSection) {
                $words = explode(' ', $line);
                if ($words[0] == '#' && $words[1] > $v_maxId)
                    $v_maxId = (int) $words[1];
            }

            if ($line == self::$start)
                $v_isSection = true;

            if ($line == self::$end) {
                $v_newId = $v_maxId++;
                $v_newCrontab[] = "# $v_newId : $p_commentary";
                $v_newCrontab[] = "$p_minute $p_hour $p_dayMonth $p_month $p_dayWeek $p_command";
            }
            $v_newCrontab[] = $line;
        }

        if ($v_isSection === false) {
            $v_newId = 1;
            $v_newCrontab[] = self::$start;
            $v_newCrontab[] = "# 1 : $p_commentary";
            $v_newCrontab[] = "$p_minute $p_hour $p_dayMonth $p_month $p_dayWeek $p_command";
            $v_newCrontab[] = self::$end;
        }

        $file = '/tmp/cron_' . time();
        $fileOpen = fopen($file, 'w');
        fwrite($fileOpen, implode("\n", $v_newCrontab));
        fclose($fileOpen);
        exec("crontab $file");
        unlink($file);
        if (!$v_newId)
            return false;
        return $v_newId;
    }

    /**
     * @param $p_id
     * @return bool
     */
    public static function exist($p_id) {
        $v_crontab = [];
        $v_isSection = false;
        exec('crontab -l', $v_crontab);
        foreach ($v_crontab as $line) {
            if ($v_isSection) {
                $words = explode(' ', $line);
                if ($words[0] == '#' && $words[1] == $p_id)
                    return true;
            }
            if ($line == self::$start)
                $v_isSection = true;
        }
        return false;
    }

    /**
     * @param $p_id
     * @return bool
     */
    public static function del($p_id) {
        $v_oldCrontab = [];
        $v_newCrontab = [];
        $v_isSection = false;
        $v_next = false;

        exec('crontab -l', $v_oldCrontab);
        foreach ($v_oldCrontab as $line) {
            if ($v_isSection) {
                $words = explode(' ', $line);
                if ($words[0] != '#' || $words[1] != $p_id)
                    if (!$v_next)
                        $v_newCrontab[] = $line;
                    else
                        $v_next = false;
                else if ($words[0] == '#' || $words[1] == $p_id)
                    $v_next = true;
            } else
                $v_newCrontab[] = $line;

            if ($line == self::$start)
                $v_isSection = true;
        }

        $file = '/tmp/cron_' . time();
        $fileOpen = fopen($file, 'w');
        fwrite($fileOpen, implode("\n", $v_newCrontab));
        fclose($fileOpen);
        exec("crontab $file");
        unlink($file);
        return true;
    }
}