<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Event\ActionRecorder\Logoff;

use osCommerce\OM\Core\{
    ActionRecorder,
    OSCOM
};

class Process
{
    public static function execute()
    {
        if (isset($_SESSION[OSCOM::getSite()]['Account'])) {
            ActionRecorder::save([
                'action' => 'logoff',
                'success' => 1,
                'user_id' => $_SESSION[OSCOM::getSite()]['Account']['id']
            ]);
        }
    }
}
