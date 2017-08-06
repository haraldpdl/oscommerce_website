<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetNewestAmbassadors
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        if ($data['limit'] < 1) {
            $data['limit'] = null;
        }

        $Qamb = $OSCOM_PDO->get('website_api_transaction_log', 'user_id', [
            'user_group' => 'member',
            'module' => 'ambassador',
            'action' => 'signup',
            'result' => 1,
            'server' => 1
        ], 'date_added desc', $data['limit']);

        return $Qamb->fetchAll();
    }
}
