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
            $sql = 'select p.title, p.code, p.image_promo, p.image_promo_url, coalesce(lang_user.title, lang_en.title) as category_title, c.code as category_code, c.sort_order as category_sort_order from :table_website_partner_transaction t, :table_website_partner p, :table_website_partner_package pp, :table_website_partner_category c left join :table_website_partner_category_lang lang_user on (c.id = lang_user.id and lang_user.languages_id = :languages_id) left join :table_website_partner_category_lang lang_en on (c.id = lang_en.id and lang_en.languages_id = :default_language_id) where p.image_promo != "" and p.id = t.partner_id and t.date_start <= now() and t.date_end >= now() and t.package_id = pp.id and pp.status = 1 and p.category_id = c.id group by p.id order by sum(t.cost) desc, p.title';
        } else {
            $sql = 'select p.title, p.code, p.image_promo, p.image_promo_url, cl.title as category_title, c.code as category_code, c.sort_order as category_sort_order from :table_website_partner_transaction t, :table_website_partner p, :table_website_partner_package pp, :table_website_partner_category c, :table_website_partner_category_lang cl where p.image_promo != "" and p.id = t.partner_id and t.date_start <= now() and t.date_end >= now() and t.package_id = pp.id and pp.status = 1 and p.category_id = c.id and c.id = cl.id and cl.languages_id = :default_language_id group by p.id order by sum(t.cost) desc, p.title';
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
