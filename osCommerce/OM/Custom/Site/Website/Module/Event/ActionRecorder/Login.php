<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Event\ActionRecorder;

class Login extends \osCommerce\OM\Core\Module\EventAbstract
{
    protected $watch = [
        'auto_login-before' => [
            __CLASS__ . '\\Auto::execute'
        ],
        'login-before' => [
            __CLASS__ . '\\Process::execute'
        ]
    ];
}
