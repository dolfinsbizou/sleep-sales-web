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

use DateTime;

final class DbDateTime extends DateTime {

    public function __construct($time = "now", DateTimeZone $timezone = null) {
        parent::__construct($time, $timezone);
    }

    public function __toString() {
        return $this->format('Y-m-d H:i:s');
    }

    public function format($format) {
        return strftime($format, $this->getTimestamp());
    }
}