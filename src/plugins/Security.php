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

class Security implements iPlugin {

    /**
     * @var array
     */
    private static $config = [];

    /**
     * @var bool
     */
    private static $bruteForce = false;

    /**
     * @param array $config
     */
    public static function start(&$config) {
        $config = array_merge([
                'intervalTime' => 300,
                'maxPostByInterval' => 30
            ], $config
        );
        self::$config = $config;
    }

    /**
     *
     */
    public static function onAstarothStart() {
        $v_time = time();
        if ((!empty(file_get_contents("php://input")) || !empty($_POST)) && isset(Astaroth::$store['session']['post.count'], Astaroth::$store['session']['post.time'])) {
            if (Astaroth::$store['session']['post.count'] >= self::$config['maxPostByInterval'] && Astaroth::$store['session']['post.time'] + self::$config['intervalTime'] >= $v_time) {
                file_put_contents("php://input", "");
                $_POST = [];
                self::$bruteForce = true;
            } else if (Astaroth::$store['session']['post.time'] + self::$config['intervalTime'] < $v_time) {
                Astaroth::$store['session']['post.count'] = 0;
                Astaroth::$store['session']['post.time'] = $v_time;
                self::$bruteForce = false;
            } else
                Astaroth::$store['session']['post.count']++;
        } else if (!isset(Astaroth::$store['session']['post.count'], Astaroth::$store['session']['post.time'])) {
            Astaroth::$store['session']['post.count'] = 0;
            Astaroth::$store['session']['post.time'] = $v_time;
        }
    }

    /**
     * @return string
     */
    public static function genToken() {
        $v_token = bin2hex(openssl_random_pseudo_bytes(60));
        Astaroth::$store['session']['token'] = $v_token;
        return $v_token;
    }

    /**
     * @param string $p_token
     * @return bool
     */
    public static function isGoodToken($p_token) {
        if (!isset(Astaroth::$store['session']['token']) || Astaroth::$store['session']['token'] != $p_token)
            return false;
        return true;
    }

    /**
     * @param string $p_mail
     * @return bool
     */
    public static function verifyMail($p_mail) {
        return (bool) filter_var($p_mail, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @return bool
     */
    public static function isBruteForce() {
        return self::$bruteForce;
    }
}