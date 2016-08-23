<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetReleaseVersions
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

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
                'has_apps' => ($Qversions->valueInt('has_apps') === 1)
            ];
        }

        return $versions;
    }
}
