<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\SQL\ANSI;

use osCommerce\OM\Core\Registry;

class GetReleases
{
    public static function execute($data)
    {
        $OSCOM_PDO = Registry::get('PDO');

        $Qreleases = $OSCOM_PDO->prepare('select id, code, title, version, filename, pkg_group, rel_group, news_id from :table_website_downloads where type = "release" order by date desc');
        $Qreleases->setCache('website-releases');
        $Qreleases->execute();

        $releases = [];

        while ($Qreleases->fetch()) {
            $releases[$Qreleases->value('pkg_group')][$Qreleases->value('code')] = [
                'id' => $Qreleases->valueInt('id'),
                'title' => $Qreleases->value('title'),
                'version' => $Qreleases->value('version'),
                'code' => $Qreleases->value('code'),
                'filename' => $Qreleases->value('filename'),
                'group' => $Qreleases->value('rel_group'),
                'news_id' => $Qreleases->valueInt('news_id')
            ];
        }

        return $releases;
    }
}
