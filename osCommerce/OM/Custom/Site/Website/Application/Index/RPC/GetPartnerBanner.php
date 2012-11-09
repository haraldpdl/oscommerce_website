<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2012 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Application\Index\RPC;

  use osCommerce\OM\Core\HTML;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;
  use osCommerce\OM\Core\Site\RPC\Controller as RPC;

  class GetPartnerBanner {
    public static function execute() {
      $OSCOM_PDO = Registry::get('PDO');

      $result = '';

      $group = 'en';

      if ( isset($_GET['group']) && in_array($_GET['group'], array('de')) ) {
        $group = HTML::outputProtected($_GET['group']);
      }

      $Qpartners = $OSCOM_PDO->prepare('select p.title, b.image, b.url, b.twitter from :table_website_partner p, :table_website_partner_banner b, :table_website_partner_transaction t where t.package_id = 3 and t.date_start <= now() and t.date_end >= now() and t.partner_id = p.id and p.id = b.partner_id and b.code = :code group by p.id order by rand()');
      $Qpartners->bindValue(':code', $group);
      $Qpartners->setCache('website_partners-all-banners-' . $group, 180);
      $Qpartners->execute();

      $data = $Qpartners->fetchAll();

      if ( count($data) > 0 ) {
        $data = $data[array_rand($data, 1)];

        $result = '<a href="' . HTML::outputProtected($data['url']) . '" target="_blank"><img src="http://www.oscommerce.com/' . OSCOM::getPublicSiteLink('images/partners/' . $data['image']) . '" width="468" height="60" alt="' . HTML::outputProtected($data['title']) . '" title="' . HTML::outputProtected($data['title']) . '" border="0" /></a>';

        if ( strlen(HTML::outputProtected($data['twitter'])) > 0 ) {
          $result .= '<script>var osCCSTwitter = "' . HTML::outputProtected($data['twitter']) . '";</script><div id="osCCSDesc"></div>';
        }
      }

      echo $result;
    }
  }
?>
