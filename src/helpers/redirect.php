<?php
/*
 * This file is part of the Astaroth package.
 *
 * (c) 2016 Victorien POTTIAU ~ Emmanuel LEROUX
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Astaroth\Helpers;

use Astaroth;

class RedirectHelper
{
    /**
     * Redirects to another url
     *
     * @see Atomik::url()
     * @param string $url The url to redirect to
     * @param bool $useUrl Whether to use Astaroth::url() on $url before redirecting
     * @param int $httpCode The redirection HTTP code
     */
    public function redirect($url, $useUrl = true, $httpCode = 302)
    {
        Astaroth::fireEvent('Astaroth::Redirect', array(&$url, &$useUrl, &$httpCode));
        if ($url === false) {
            return;
        }
        
        if ($useUrl) {
            $url = Astaroth::url($url);
        }

        if (isset($_SESSION)) {
            // seems to prevent a php bug with session before redirections
            session_regenerate_id(true);
            // avoid loosing the session
        }
        
        header('Location: ' . $url, true, $httpCode);
        Astaroth::end(true, false);
    }
}
