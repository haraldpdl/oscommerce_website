<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

  use osCommerce\OM\Core\HTML;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  class GetPartnerBanner {
    public static function execute() {
      $OSCOM_PDO = Registry::get('PDO');
      $OSCOM_Template = Registry::get('Template');

      $group = 'en';

      if ( isset($_GET['group']) && in_array($_GET['group'], array('de')) ) {
        $group = HTML::outputProtected($_GET['group']);
      }

      $Qpartners = $OSCOM_PDO->prepare('select p.title, p.image_small, b.image, b.url, su.status_update from :table_website_partner p left join :table_website_partner_status_update su on (p.id = su.partner_id and su.code = :sucode), :table_website_partner_banner b, :table_website_partner_transaction t where t.package_id = 3 and t.date_start <= now() and t.date_end >= now() and t.partner_id = p.id and p.id = b.partner_id and b.code = :code group by p.id order by rand()');
      $Qpartners->bindValue(':sucode', $group);
      $Qpartners->bindValue(':code', $group);
      $Qpartners->setCache('website_partners-all-banners-' . $group, 180);
      $Qpartners->execute();

      $data = $Qpartners->fetchAll();

      if ( count($data) > 0 ) {
        $data = $data[array_rand($data, 1)];

        $result = array('url' => HTML::outputProtected($data['url']),
                        'image' => 'http://www.oscommerce.com/' . OSCOM::getPublicSiteLink('images/partners/' . $data['image']),
                        'title' => HTML::outputProtected($data['title']),
                        'status_update' => !empty($data['status_update']) ? $OSCOM_Template->parseContent(HTML::outputProtected($data['status_update']), array('url')) : null);

        header('Content-Type: application/javascript');

        $json = json_encode($result);

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

        echo $output;
      }
    }
  }
?>