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
use PHPMailer;

class MailException extends Exception { }

class Mail extends PHPMailer implements iPlugin {

    /**
     * @var array
     */
    public static $config = [];

    /**
     * @var Mail
     */
    private static $instance;

    public static function start(&$config) {
        $config = array_merge([
                'smtp' => false,
                'host' => '',
                'port' => 222,
                'SMTPAuth' => false,
                'SMTPSecure' => 'tls',
                'username' => '',
                'password' => '',
                'exceptions' => false
            ], $config
        );
        self::$instance = new Mail();
        Astaroth::set('mail', self::$instance);
    }

    public function __construct() {
        parent::__construct(self::$config['exceptions']);
        if (self::$config['smtp'])
            $this->isSMTP();
        $this->Host = self::$config['host'];
        $this->SMTPAuth = self::$config['SMTPAuth'];
        $this->Username = self::$config['username'];
        $this->Password = self::$config['password'];
        $this->SMTPSecure = self::$config['SMTPSecure'];
        $this->Port = self::$config['port'];
    }
}

