<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Scripts\GenerateInvoices\Module;

use osCommerce\OM\Core\{
    Cache as OldCache,
    HttpRequest,
    OSCOM
};

use osCommerce\OM\Core\Site\Apps\Cache;

class Ambassador
{
    public static function beforeMail(array $user, array $invoice)
    {
        OldCache::clear('users-' . $user['id'] . '-invoices-check');
        OldCache::clear('users-' . $user['id'] . '-invoices');
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
