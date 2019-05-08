<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\{
    HTML,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Partner;

class GetPartnerBanner
{
    public static function execute()
    {
        header('Access-Control-Allow-Origin: *');

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header('Access-Control-Allow-Headers: X-Requested-With');
            exit;
        }

        $OSCOM_Cache = Registry::get('Cache');
        $OSCOM_PDO = Registry::get('PDO');
        $OSCOM_Template = Registry::get('Template');

        $languages = [
            'en' => 1,
            'de' => 2
        ];

        $language_paths = [
            '1' => 'en_US',
            '2' => 'de_DE'
        ];

        $group = 'en';
        $forum_channel_id = null;

        if (isset($_GET['group']) && in_array($_GET['group'], ['de'])) {
            $group = HTML::outputProtected($_GET['group']);
        }

        if (isset($_GET['forumid']) && is_numeric($_GET['forumid']) && ($_GET['forumid'] > 0)) {
            $forum_channel_id = (int)$_GET['forumid'];
        }

        if ($OSCOM_Cache->read('website_partners-all-banners-' . $group, 180)) {
            $data = $OSCOM_Cache->getCache();
        } else {
            $sql = <<<EOD
select
  p.id,
  pi.title,
  pi.banner_image as image,
  pi.banner_url as url,
  pi.status_update,
  pi.languages_id,
  group_concat(fc.channel_id) as channel_ids
from
  :table_website_partner p
    left join
      :table_website_partner_forum_channels fc
        on
          (p.id = fc.partner_id and fc.code = :fccode),
  :table_website_partner_info pi,
  :table_website_partner_transaction t
where
  t.package_id = 3 and
  t.date_start <= now() and
  t.date_end >= now() and
  t.partner_id = p.id and
  p.id = pi.partner_id and
  pi.languages_id = :languages_id and
  pi.banner_image != '' and
  pi.banner_url != ''
group by
  p.id
order by
  rand()
EOD;

            $Qpartners = $OSCOM_PDO->prepare($sql);
            $Qpartners->bindValue(':fccode', 'en');
            $Qpartners->bindInt(':languages_id', $languages['en']);
            $Qpartners->execute();

            $data = $Qpartners->fetchAll();

            if ($group != 'en') {
                $Qpartners->bindValue(':fccode', $group);
                $Qpartners->bindInt(':languages_id', $languages[$group]);
                $Qpartners->execute();

                while ($Qpartners->fetch()) {
                    $found = false;

                    foreach ($data as $k => $v) {
                        if ($Qpartners->value('id') == $v['id']) {
                            $found = true;

                            $data[$k] = $Qpartners->toArray();

                            break;
                        }
                    }

                    if ($found === false) {
                        $data[] = $Qpartners->toArray();
                    }
                }
            }

            $OSCOM_Cache->write($data);
        }

        if (count($data) > 0) {
            if (isset($forum_channel_id)) {
                $fc_koeln = [];

                foreach ($data as $p) {
                    if (!empty($p['channel_ids']) && in_array($forum_channel_id, explode(',', $p['channel_ids']))) {
                        $fc_koeln[] = $p;
                    }
                }

                if (!empty($fc_koeln)) {
                    $data = $fc_koeln;
                }
            }

            if (count($data) > 1) {
                $data = $data[array_rand($data)];
            } else {
                $data = $data[0];
            }

            $result = [
                'url' => HTML::outputProtected($data['url']),
                'image' => 'https://www.oscommerce.com/' . OSCOM::getPublicSiteLink('images/partners/' . $language_paths[$data['languages_id']] . '/' . $data['image']),
                'title' => HTML::outputProtected($data['title']),
                'status_update' => !empty($data['status_update']) ? $OSCOM_Template->parseContent(HTML::outputProtected($data['status_update']), ['url']) : null,
                'categories' => Partner::getCategories()
            ];

            header('Content-Type: application/json');

            echo json_encode($result);
        }
    }
}
