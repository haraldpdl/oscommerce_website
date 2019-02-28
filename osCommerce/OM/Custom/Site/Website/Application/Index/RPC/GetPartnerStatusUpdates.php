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

class GetPartnerStatusUpdates
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

        $group = 'en';

        if (isset($_GET['group']) && in_array($_GET['group'], ['de'])) {
            $group = HTML::outputProtected($_GET['group']);
        }

        if ($OSCOM_Cache->read('website_partners-all-status_update-' . $group, 60)) {
            $statuses = $OSCOM_Cache->getCache();
        } else {
            $sql = <<<EOD
select
  p.id,
  pi.code,
  pi.title,
  pi.url,
  pi.status_update,
  c.title as category_title,
  c.code as category_code
from
  :table_website_partner p,
  :table_website_partner_info pi,
  :table_website_partner_transaction t,
  :table_website_partner_category c
where
  t.package_id = 3 and
  t.date_start <= now() and
  t.date_end >= now() and
  t.partner_id = p.id and
  p.id = pi.partner_id and
  pi.languages_id = :languages_id and
  pi.status_update != '' and
  p.category_id = c.id
group by
  p.id
order by
  rand()
limit
  5
EOD;

            $Qpartners = $OSCOM_PDO->prepare($sql);
            $Qpartners->bindInt(':languages_id', $languages['en']);
            $Qpartners->execute();

            $statuses = $Qpartners->fetchAll();

            if ($group != 'en') {
                $Qpartners->bindInt(':languages_id', $languages[$group]);
                $Qpartners->execute();

                while ($Qpartners->fetch()) {
                    $found = false;

                    foreach ($statuses as $k => $v) {
                        if ($Qpartners->value('id') == $v['id']) {
                            $found = true;

                            $statuses[$k] = $Qpartners->toArray();

                            break;
                        }
                    }

                    if ($found === false) {
                        $statuses[] = $Qpartners->toArray();
                    }
                }
            }

            $OSCOM_Cache->write($statuses);
        }

        $result = [];

        foreach ($statuses as $s) {
            $status_update = $s['status_update'];

// Oh yes, I may just sneak in an old switcheroo!
            if (strpos($status_update, '{url}') !== false) {
                $status_update = preg_replace('/(\{url\})(.*)(\{url\})/s', '{partnerurl ' . $s['code'] . '}$2{partnerurl}', $status_update);
            }

            $result[] = [
                'code' => $s['code'],
                'title' => HTML::outputProtected($s['title']),
                'url' => OSCOM::getLink('Website', 'Services', 'Redirect=' . $s['code'], 'SSL', false),
                'status_update' => $OSCOM_Template->parseContent(HTML::outputProtected($status_update), ['partnerurl']),
                'category_title' => $s['category_title'],
                'category_code' => $s['category_code']
            ];
        }

        header('Cache-Control: max-age=3600, must-revalidate');
        header_remove('Pragma');
        header('Content-Type: application/javascript');

        echo json_encode($result);
    }
}
