<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Event\ActionRecorder;

class Invoices extends \osCommerce\OM\Core\Module\EventAbstract
{
    protected $watch = [
        'invoice-download-before' => [
            __CLASS__ . '\\Download::execute'
        ]
    ];
}
