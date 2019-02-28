<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Partner;

use osCommerce\OM\Core\Registry;

class GetPackages
{
    public static function execute(array $data): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        if (isset($data['language_id'])) {
            $sql = 'select l.code, coalesce(lang_user.title, lang_en.title) as title, coalesce(lang_user.title_short, lang_en.title_short) as title_short from :table_website_partner_package l left join :table_website_partner_package_lang lang_user on (lang_user.id = l.id and lang_user.languages_id = :languages_id) left join :table_website_partner_package_lang lang_en on (lang_en.id = l.id and lang_en.languages_id = :default_language_id) where l.status = 1 order by l.sort_order, title';
        } else {
            $sql = 'select l.code, ll.title, ll.title_short from :table_website_partner_package l, :table_website_partner_package_lang ll where l.status = 1 and l.id = ll.id and ll.languages_id = :default_language_id order by l.sort_order, ll.title';
        }

        $Qpkgs = $OSCOM_PDO->prepare($sql);

        if (isset($data['language_id'])) {
            $Qpkgs->bindInt(':languages_id', $data['language_id']);
        }

        $Qpkgs->bindInt(':default_language_id', $data['default_language_id']);
        $Qpkgs->setCache('website_partner_pkgs-lang' . ($data['language_id'] ?? $data['default_language_id']));
        $Qpkgs->execute();

        return $Qpkgs->fetchAll();
    }
}
