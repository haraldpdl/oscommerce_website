<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetPartnerPromotions
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (isset($data['language_id'])) {
            $sql = <<<EOD
select
  coalesce(pi_lang_user.title, pi_lang_en.title) as title,
  coalesce(pi_lang_user.code, pi_lang_en.code) as code,
  coalesce(pi_lang_user.image_promo, pi_lang_en.image_promo) as image_promo,
  coalesce(pi_lang_user.image_promo_url, pi_lang_en.image_promo_url) as image_promo_url,
  coalesce(c_lang_user.title, c_lang_en.title) as category_title,
  c.code as category_code,
  c.sort_order as category_sort_order
from
  :table_website_partner_transaction t,
  :table_website_partner p
    left join
      :table_website_partner_info pi_lang_user
        on
          (p.id = pi_lang_user.partner_id and pi_lang_user.languages_id = :languages_id and pi_lang_user.image_promo != '')
    left join
      :table_website_partner_info pi_lang_en
        on
          (p.id = pi_lang_en.partner_id and pi_lang_en.languages_id = :default_language_id and pi_lang_en.image_promo != ''),
  :table_website_partner_package pp,
  :table_website_partner_category c
    left join
      :table_website_partner_category_lang c_lang_user
        on
          (c.id = c_lang_user.id and c_lang_user.languages_id = :languages_id)
    left join
      :table_website_partner_category_lang c_lang_en
        on
          (c.id = c_lang_en.id and c_lang_en.languages_id = :default_language_id)
where
  p.id = t.partner_id and
  t.date_start <= now() and
  t.date_end >= now() and
  t.package_id = pp.id and
  pp.id = 3 and
  pp.status = 1 and
  p.category_id = c.id
group by
  p.id
order by
  sum(t.cost) desc,
  title
EOD;
        } else {
            $sql = <<<EOD
select
  pi.title,
  pi.code,
  pi.image_promo,
  pi.image_promo_url,
  cl.title as category_title,
  c.code as category_code,
  c.sort_order as category_sort_order
from
  :table_website_partner_transaction t,
  :table_website_partner p,
  :table_website_partner_info pi,
  :table_website_partner_package pp,
  :table_website_partner_category c,
  :table_website_partner_category_lang cl
where
  pi.image_promo != '' and
  pi.partner_id = p.id and
  p.id = t.partner_id and
  t.date_start <= now() and
  t.date_end >= now() and
  t.package_id = pp.id and
  pp.id = 3 and
  pp.status = 1 and
  p.category_id = c.id and
  c.id = cl.id and
  pi.languages_id = cl.languages_id and
  cl.languages_id = :default_language_id
group by
  p.id
order by
  sum(t.cost) desc,
  pi.title
EOD;
        }

        $Qpromos = $OSCOM_PDO->prepare($sql);

        if (isset($data['language_id'])) {
            $Qpromos->bindInt(':languages_id', $data['language_id']);
        }

        $Qpromos->bindInt(':default_language_id', $data['default_language_id']);
        $Qpromos->setCache('website_partner_promotions-lang' . ($data['language_id'] ?? $data['default_language_id']), 720);
        $Qpromos->execute();

        return $Qpromos->fetchAll();
    }
}
