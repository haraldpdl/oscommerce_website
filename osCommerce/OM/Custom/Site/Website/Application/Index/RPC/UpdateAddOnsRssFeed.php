<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\{
    HttpRequest,
    OSCOM
};

use osCommerce\OM\Core\Site\RPC\Controller as RPC;

class UpdateAddOnsRssFeed
{
    public static function execute()
    {
        $result = [];

        if (isset($_POST['key']) && ($_POST['key'] == OSCOM::getConfig('cron_key'))) {
            $feed_1 = HttpRequest::getResponse(['url' => 'https://www.oscommerce.com/public/sites/Website/rss/legacy_addons.rdf']);
            $feed_2 = HttpRequest::getResponse(['url' => 'https://www.oscommerce.com/public/sites/Website/rss/legacy_packages.rdf']);

            if (!empty($feed_1) && !empty($feed_2)) {
                if ((file_put_contents(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/rss/addons.xml', $feed_1, LOCK_EX) !== false) && (file_put_contents(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/Website/rss/addons_packages.xml', $feed_2, LOCK_EX) !== false)) {
                    $result['rpcStatus'] = RPC::STATUS_SUCCESS;
                }
            }
        }

        if (!isset($result['rpcStatus'])) {
            $result['rpcStatus'] = RPC::STATUS_NO_ACCESS;
        }

        echo json_encode($result);
    }
}
