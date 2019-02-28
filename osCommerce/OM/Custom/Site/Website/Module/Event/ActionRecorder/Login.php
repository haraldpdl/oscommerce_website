<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
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
