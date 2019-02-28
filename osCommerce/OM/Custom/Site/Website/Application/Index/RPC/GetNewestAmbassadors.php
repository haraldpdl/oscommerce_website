<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\Site\Website\Users;

class GetNewestAmbassadors
{
    public static function execute()
    {
        echo json_encode(Users::getNewestAmbassadors(4));
    }
}
