<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetPartnerCategories
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (isset($data['language_id'])) {
            $sql = 'select coalesce(lang_user.title, lang_en.title) as title, c.code from :table_website_partner_category c left join :table_website_partner_category_lang lang_user on (c.id = lang_user.id and lang_user.languages_id = :languages_id) left join :table_website_partner_category_lang lang_en on (c.id = lang_en.id and lang_en.languages_id = :default_language_id), :table_website_partner_transaction t, :table_website_partner p, :table_website_partner_package pp where t.date_start <= now() and t.date_end >= now() and t.package_id = pp.id and pp.status = 1 and t.partner_id = p.id and p.category_id = c.id group by c.id order by c.sort_order, title';
        } else {
            $sql = 'select cl.title, c.code from :table_website_partner_category c, :table_website_partner_category_lang cl, :table_website_partner_transaction t, :table_website_partner p, :table_website_partner_package pp where t.date_start <= now() and t.date_end >= now() and t.package_id = pp.id and pp.status = 1 and t.partner_id = p.id and p.category_id = c.id and c.id = cl.id and cl.languages_id = :default_language_id group by c.id order by c.sort_order, cl.title';
        }

        $Qgroups = $OSCOM_PDO->prepare($sql);

        if (isset($data['language_id'])) {
            $Qgroups->bindInt(':languages_id', $data['language_id']);
        }

        $Qgroups->bindInt(':default_language_id', $data['default_language_id']);
        $Qgroups->setCache('website_partner_categories-lang' . ($data['language_id'] ?? $data['default_language_id']), 720);
        $Qgroups->execute();

        return $Qgroups->fetchAll();
    }
}
