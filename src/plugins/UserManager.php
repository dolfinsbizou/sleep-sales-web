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
use Exception;

final class UserManagerException extends Exception {}

/**
 * Class UserManager
 * @package Astaroth
 */
final class UserManager implements iPlugin {

    /**
     * @const string
     */
    const REMEMBER_KEY = 'remember_key';

    /**
     * @var string
     */
    private static $KEY_SECURE = 'S41P34TS75?M.';

    /**
     * @var string
     */
    private static $SALT = '$(dgfqvkl;:a*aswcshwrq9cr$';

    /**
     * @var array
     */
    private static $config = [];

    /**
     * @var bool
     */
    private static $connected;

    /**
     * @param $config
     */
    public static function start(&$config) {
        $config = array_merge([

            /* @var string */
                'class_user' => '',

            /* @var string */
                'attr_username' => '',

            /* @var string */
                'attr_passwd' => '',

            /* @var string */
                'attr_remember' => '',

            /* @var bool */
                'cookie_remember' => false

            ], $config
        // TODO : LOG
        );
        self::$config = &$config;
    }

    /**
     * @throws UserManagerException
     */
    public static function onAstarothStart() {
        $v_plugins = array_keys(Astaroth::get('plugins'));
        if (!in_array('Db', $v_plugins) || !in_array('Session', $v_plugins) || self::$config['cookie_remember'] && !in_array('Cookie', $v_plugins))
            throw new UserManagerException("Need Db ans Session plugin !");
        if (isset(Astaroth::$store['session']['login']['key'], Astaroth::$store['session']['login']['username'], Astaroth::$store['session']['login']['passwd'])) {
            if (Astaroth::$store['session']['login']['key'] !== self::getHash(Astaroth::$store['session']['login']['username'] . Astaroth::$store['session']['login']['passwd'])) {
                self::disconnect();
                return;
            }
            $v_account = new self::$config['class_user']([self::$config['attr_username'] => Astaroth::$store['session']['login']['username'], self::$config['attr_passwd'] => Astaroth::$store['session']['login']['passwd']]);
            if (!$v_account->Exist()) {
                self::disconnect();
                return;
            }
            self::$connected = true;
        } else if (($v_remember_key = Cookie::get(self::REMEMBER_KEY)) !== false) {
            if (!self::$config['cookie_remember']) {
                self::unsetRemember();
                return;
            }
            $v_account = new self::$config['class_user']([self::$config['attr_remember'] => $v_remember_key]);
            if ($v_account->Get([self::$config['attr_username'], self::$config['attr_passwd']])) {
                Astaroth::$store['session']['login'] = [
                    'key' => self::getHash($v_account[self::$config['attr_username']] . $v_account[self::$config['attr_passwd']]),
                    'username' => $v_account[self::$config['attr_username']],
                    'passwd' => $v_account[self::$config['attr_passwd']]
                ];
                self::$connected = true;
            } else {
                self::unsetRemember();
            }
        }
    }

    /**
     * @return bool
     */
    public static function isConnected() {
        return (bool) self::$connected;
    }

    /**
     * @param string $p_username
     * @param string $p_passwd
     * @param bool $p_remember
     * @return bool
     */
    public static function connect($p_username, $p_passwd, $p_remember = false) {
        try {
            $v_account = new self::$config['class_user']([self::$config['attr_username'] => $p_username, self::$config['attr_passwd'] => self::getHash($p_passwd)]);
            if (!$v_account->Exist())
                return false;
        } catch (DbClassException $e) {
            return false;
        }
        self::$connected = true;
        Astaroth::$store['session']['login'] = [
            'key' => self::getHash($p_username . self::getHash($p_passwd)),
            'username' => $p_username,
            'passwd' => self::getHash($p_passwd)
        ];
        if (self::$config['cookie_remember'] && $p_remember) {
            $v_key = self::getHash($p_username . time() . $p_passwd);
            Cookie::set(self::REMEMBER_KEY, $v_key, Cookie::ONE_DAY * 7);
            $v_account[self::$config['attr_remember']] = $v_key;
            $v_account->setWhere()
                ->add(DbWhereOperator::EQUAL, self::$config['attr_username'], $p_username)
                ->add(DbWhereOperator::EQUAL, self::$config['attr_passwd'], self::getHash($p_passwd));
            $v_account->Save(false, true);
        }
        return true;
    }

    /**
     *
     */
    public static function disconnect() {
        if (!self::$connected)
            return;
        self::unsetSession();
        if (($v_remember_key = Cookie::get(self::REMEMBER_KEY)) !== false) {
            $v_account = new self::$config['class_user']([self::$config['attr_remember'] => $v_remember_key]);
            if ($v_account->Get([self::$config['attr_username'], self::$config['attr_passwd'], self::$config['attr_remember']])) {
                $v_account[self::$config['attr_remember']] = null;
                $v_account->setWhere()
                    ->add(DbWhereOperator::EQUAL, self::$config['attr_username'], $v_account[self::$config['attr_username']])
                    ->add(DbWhereOperator::EQUAL, self::$config['attr_passwd'], $v_account[self::$config['attr_passwd']]);
                $v_account->Save(false, true);
            }
            self::unsetRemember();
        }
        self::$connected = false;
    }

    public static function getUser($p_column = '*') {
        $v_account = new self::$config['class_user']([self::$config['attr_username'] => Astaroth::$store['session']['login']['username'], self::$config['attr_passwd'] => Astaroth::$store['session']['login']['passwd']]);
        // Todo Verif
        $v_account->Get($p_column);
        return $v_account;
    }

    /**
     *
     */
    public static function unsetRemember() {
        if (Cookie::get(self::REMEMBER_KEY) === false)
            return;
        Cookie::del(self::REMEMBER_KEY);
    }

    /**
     *
     */
    public static function unsetSession() {
        if (!isset(Astaroth::$store['session']['login']))
            return;
        unset(Astaroth::$store['session']['login']);
    }

    /**
     * @param string $p_hash
     * @return string
     */
    public static function getHash($p_hash) {
        $v_options = [
            'cost' => 10,
            'salt' => self::$SALT,
        ];
        return password_hash(self::$KEY_SECURE . $p_hash . self::$KEY_SECURE, PASSWORD_BCRYPT, $v_options);
    }
}