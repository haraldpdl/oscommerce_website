<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\Cache;

use osCommerce\OM\Core\Site\Apps\Apps;

class GetLatestAddons
{
    public static function execute()
    {
        $result = [];

        $OSCOM_Cache = new Cache();

        if ($OSCOM_Cache->read('website-addons-listing-last5', 60)) {
            $result = $OSCOM_Cache->getCache();
        } else {
            $listing = Apps::getListing();

            if (isset($listing['entries']) && !empty($listing['entries'])) {
                $counter = 0;

                foreach ($listing['entries'] as $l) {
                    $date = \DateTime::createFromFormat('Ymd His', $l['last_update_date']);

                    if ($date === false) {
                        continue;
                    }

                    $date_errors = \DateTime::getLastErrors();

                    if (($date_errors['warning_count'] !== 0) || ($date_errors['error_count'] !== 0)) {
                        continue;
                    }

                    $counter++;

                    $result[] = [
                        'title' => $l['title'],
                        'link' => 'https://apps.oscommerce.com/' . $l['public_id'],
                        'date' => $date->format('Y-m-d H:i:s')
                    ];

                    if ($counter === 5) {
                        break;
                    }
                }

                if (count($result) === 5) {
                    $OSCOM_Cache->write($result);
                }
            }
        }

        header('Cache-Control: max-age=10800, must-revalidate');
        header_remove('Pragma');
        header('Content-Type: application/javascript');

        echo json_encode($result);
    }
}
