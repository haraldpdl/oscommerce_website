<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Scripts\GenerateInvoices\Module;

use osCommerce\OM\Core\{
    HttpRequest,
    OSCOM
};

use osCommerce\OM\Core\Site\Apps\Cache;

class Ambassador
{
    public static function beforeMail(array $user, array $invoice)
    {
    }

    public static function cleanup()
    {
        $OSCOM_Cache = new Cache('ambassadors-newest-NS');
        $OSCOM_Cache->delete();

        HttpRequest::getResponse([
            'url' => 'https://forums.oscommerce.com/index.php?oscomAction=clearCache&oscomModule=ambassadors',
            'parameters' => 'key=' . OSCOM::getConfig('cron_key')
        ]);
    }
}
