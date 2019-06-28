<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\Releases;

use osCommerce\OM\Core\Registry;

class GetVersions
{
    public static function execute(): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        // $Qversions = $OSCOM_PDO->prepare('select v.*, d.date, d.news_id from :table_website_release_versions v, :table_website_downloads d where v.downloads_id = d.id order by v.major_ver, v.minor_ver, v.patch_ver');
        $Qversions = $OSCOM_PDO->prepare('select * from :table_website_release_versions order by major_ver, minor_ver, patch_ver');
        $Qversions->setCache('website-releases-versions');
        $Qversions->execute();

        $versions = [];

        while ($Qversions->fetch()) {
            $versions[] = [
                'id' => $Qversions->valueInt('id'),
                'version' => $Qversions->valueInt('major_ver') . '.' . $Qversions->valueInt('minor_ver') . '.' . $Qversions->valueInt('patch_ver'),
                'major_ver' => $Qversions->valueInt('major_ver'),
                'minor_ver' => $Qversions->valueInt('minor_ver'),
                'patch_ver' => $Qversions->valueInt('patch_ver'),
                'has_apps' => ($Qversions->valueInt('has_apps') === 1),
                // 'parent_id' => $Qversions->hasValue('parent_id') ? $Qversions->valueInt('parent_id') : null,
                // 'date' => $Qversions->value('date'),
                // 'news_id' => $Qversions->valueInt('news_id')
            ];
        }

        return $versions;
    }
}
