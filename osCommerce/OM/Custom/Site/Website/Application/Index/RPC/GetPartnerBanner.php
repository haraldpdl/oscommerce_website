<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

use osCommerce\OM\Core\HTML;
use osCommerce\OM\Core\OSCOM;
use osCommerce\OM\Core\Registry;

class GetPartnerBanner
{
    public static function execute()
    {
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
  pi.image_small,
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
  pi.banner_image != ''
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
                $data = $data[mt_rand(0, count($data) - 1)];
            } else {
                $data = $data[0];
            }

            $result = [
                'url' => HTML::outputProtected($data['url']),
                'image' => 'https://ssl.oscommerce.com/' . OSCOM::getPublicSiteLink('images/partners/' . $language_paths[$data['languages_id']] . '/' . $data['image']),
                'title' => HTML::outputProtected($data['title']),
                'status_update' => !empty($data['status_update']) ? $OSCOM_Template->parseContent(HTML::outputProtected($data['status_update']), ['url']) : null
            ];

            $json = json_encode($result);

            if (isset($_GET['onlyjson']) && ($_GET['onlyjson'] == 'true')) {
                $output = $json;
            } else {
                header('Content-Type: application/javascript');

                if (isset($_GET['format']) && ($_GET['format'] = 'jquery')) {
                    $output = <<<JAVASCRIPT
var oscPartner = $json

function oscLoadBanner() {
  $('#osCCS').html('<div id="osCCSImage" class="ipsColumn ipsColumn_veryWide"><a href="' + oscPartner.url + '" target="_blank"><img src="' + oscPartner.image + '" alt="' + oscPartner.title + '" border="0" /></a></div>');
}

function oscLoadStatusUpdate() {
  $('#osCCS').append('<div id="osCCSDesc" class="ipsColumn ipsColumn_fluid"><span class="ipsType_minorHeading" style="font-weight: bold;"><a href="' + oscPartner.url + '" target="_blank">' + oscPartner.title + '</a></span><br />' + oscPartner.status_update + '</div>');
}

$(function() {
  oscLoadBanner();

  if ( oscPartner.status_update != null ) {
    oscLoadStatusUpdate();
  }
});
JAVASCRIPT;
                } else {
                    $output = <<<JAVASCRIPT
var oscPartner = $json

function oscLoadBanner() {
  $('osCCS').update('<a href="' + oscPartner.url + '" target="_blank"><img src="' + oscPartner.image + '" width="468" height="60" alt="' + oscPartner.title + '" border="0" /></a>');
}

function oscLoadStatusUpdate() {
  $('osCCS').insert('<div id="osCCSDesc"><p><a href="' + oscPartner.url + '" target="_blank"><strong>' + oscPartner.title + '</strong></a></p><p>' + oscPartner.status_update + '</p></div>');
}

document.observe('dom:loaded', function() {
  oscLoadBanner();

  if ( oscPartner.status_update != null ) {
    oscLoadStatusUpdate();
  }
});
JAVASCRIPT;
                }
            }

            echo $output;
        }
    }
}
