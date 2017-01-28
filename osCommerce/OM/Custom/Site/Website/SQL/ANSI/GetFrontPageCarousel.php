<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetFrontPageCarousel
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (isset($data['language_id'])) {
            $sql = <<<EOD
select
  c.*,
  coalesce(pi_lang_user.carousel_image, pi_lang_en.carousel_image) as carousel_image,
  coalesce(pi_lang_user.carousel_url, pi_lang_en.carousel_url) as carousel_url,
  coalesce(pi_lang_user.carousel_title, pi_lang_en.carousel_title) as carousel_title
from
  :table_website_carousel c
    left join
      :table_website_partner_info pi_lang_user
        on
          (c.partner_id = pi_lang_user.partner_id and pi_lang_user.languages_id = :languages_id)
    left join
      :table_website_partner_info pi_lang_en
        on
          (c.partner_id = pi_lang_en.partner_id and pi_lang_en.languages_id = :default_language_id)
where
  c.status = 1
order by
  c.sort_order
EOD;
        } else {
            $sql = <<<EOD
select
  c.*,
  pi.carousel_image,
  pi.carousel_url,
  pi.carousel_title
from
  :table_website_carousel c
    left join
      :table_website_partner_info pi
        on
          (c.partner_id = pi.partner_id and pi.languages_id = :default_language_id)
where
  c.status = 1
order by
  c.sort_order
EOD;
        }

        $Q = $OSCOM_PDO->prepare($sql);

        if (isset($data['language_id'])) {
            $Q->bindInt(':languages_id', $data['language_id']);
        }

        $Q->bindInt(':default_language_id', $data['default_language_id']);
        $Q->setCache('website_carousel_frontpage-lang' . ($data['language_id'] ?? $data['default_language_id']));
        $Q->execute();

        return $Q->fetchAll();
    }
}
