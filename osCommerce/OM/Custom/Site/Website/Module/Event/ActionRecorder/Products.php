<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Event\ActionRecorder;

class Products extends \osCommerce\OM\Core\Module\EventAbstract
{
    protected $watch = [
        'download-before' => [
            __CLASS__ . '\\Download::execute'
        ]
    ];
}
