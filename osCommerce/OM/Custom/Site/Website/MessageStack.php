<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website;

  use osCommerce\OM\Core\OSCOM;

  class MessageStack extends \osCommerce\OM\Core\MessageStack {
    public function get($group = null) {
      if ( empty($group) ) {
        $group = OSCOM::getSiteApplication();
      }

      $result = false;

      if ( $this->exists($group) ) {
        $result = '';

        foreach ( $this->_data[$group] as $message ) {
          if ( $message['type'] == 'error' ) {
            $message['type'] = 'danger';
          }

          $result .= '<div class="alert alert-' . $message['type'] . '">' . $message['text'] . '</div>';
        }

        unset($this->_data[$group]);
      }

      return $result;
    }
  }
?>
