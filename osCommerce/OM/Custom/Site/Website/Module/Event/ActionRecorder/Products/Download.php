<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Module\Event\ActionRecorder\Products;

use osCommerce\OM\Core\{
    ActionRecorder,
    OSCOM
};

use osCommerce\OM\Core\Site\Website\Download as DownloadClass;

class Download
{
    public static function execute($file)
    {
        $ar = [
            'action' => 'download',
            'success' => 1,
            'user_id' => $_SESSION[OSCOM::getSite()]['Account']['id'] ?? null,
            'identifier' => $file
        ];

        if (!DownloadClass::exists($file)) {
            $ar['success'] = 0;
            $ar['result'] = 'nonexistent';
        } else {
            if (!is_numeric($file)) {
                $ar['identifier'] = DownloadClass::getID($file) . ':' . $ar['identifier'];
            }
        }

        ActionRecorder::save($ar);
    }
}
