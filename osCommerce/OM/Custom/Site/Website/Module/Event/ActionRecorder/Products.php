<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
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
