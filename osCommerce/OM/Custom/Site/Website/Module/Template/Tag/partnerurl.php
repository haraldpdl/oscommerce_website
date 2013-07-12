<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Module\Template\Tag;

  use osCommerce\OM\Core\OSCOM;

  use osCommerce\OM\Core\Site\Website\Partner;

  class partnerurl extends \osCommerce\OM\Core\Template\TagAbstract {
    static protected $_parse_result = false;

    static public function execute($string) {
      $args = func_get_args();
      $code = trim($args[1]);

      $params = explode('|', $string, 2);

      if ( empty($params[0]) ) {
        return (isset($params[1]) && !empty($params[1]) ? $params[1] : '');
      }

      $url = $params[0];

      $new_url = OSCOM::callDB('Website\GetPartnerStatusUpdateUrlCode', array('partner_id' => Partner::get($code, 'id'), 'url' => $url), 'Site');

      return '<a href="' . OSCOM::getLink('Website', 'Services', 'Redirect=' . $code . '&url=' . $new_url, 'NONSSL', false) . '" target="_blank">' . (isset($params[1]) ? $params[1] : $params[0]) . '</a>';
    }
  }
?>
