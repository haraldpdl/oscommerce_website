<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetPartnerPackageLevels
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (isset($data['language_id'])) {
            $sql = 'select l.id, l.duration_months, l.price, l.default_selected, coalesce(lang_user.title, lang_base.title) as title from :table_website_partner_package_levels l left join :table_website_partner_package_levels_lang lang_user on (l.id = lang_user.id and lang_user.languages_id = :languages_id) left join :table_website_partner_package_levels_lang lang_base on (l.id = lang_base.id and lang_base.languages_id = :default_language_id), :table_website_partner_package p where p.code = :package_code and p.id = l.package_id and l.status = 1 order by l.sort_order, title';
        } else {
            $sql = 'select l.id, l.duration_months, l.price, l.default_selected, ll.title from :table_website_partner_package_levels l, :table_website_partner_package_levels_lang ll, :table_website_partner_package p where p.code = :package_code and p.id = l.package_id and l.status = 1 and l.id = ll.id and ll.languages_id = :default_language_id order by l.sort_order, ll.title';
        }

        $Qlevels = $OSCOM_PDO->prepare($sql);

        if (isset($data['language_id'])) {
            $Qlevels->bindInt(':languages_id', $data['language_id']);
        }

        $Qlevels->bindInt(':default_language_id', $data['default_language_id']);
        $Qlevels->bindValue(':package_code', $data['package_code']);
        $Qlevels->setCache('website_partner_pkgs_lvls-code' . $data['package_code'] . '-lang' . ($data['language_id'] ?? $data['default_language_id']));
        $Qlevels->execute();

        return $Qlevels->fetchAll();
    }
}
