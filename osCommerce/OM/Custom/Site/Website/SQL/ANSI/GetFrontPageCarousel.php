<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2015 osCommerce; http://www.oscommerce.com
 * @license BSD; http://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetFrontPageCarousel
{
    public static function execute()
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Q = $OSCOM_PDO->prepare('select c.*, p.carousel_image, p.carousel_url, p.carousel_title from :table_website_carousel c left join :table_website_partner p on (c.partner_id = p.id) where c.status = 1 order by c.sort_order');
        $Q->setCache('website_carousel_frontpage');
        $Q->execute();

        return $Q->fetchAll();
    }
}
