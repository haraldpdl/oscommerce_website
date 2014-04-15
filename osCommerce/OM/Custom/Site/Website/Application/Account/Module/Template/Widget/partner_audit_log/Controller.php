<?php
/**
 * osCommerce Website
 *
 * @copyright Copyright (c) 2014 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website\Module\Template\Widget\partner_audit_log;

  use osCommerce\OM\Core\HTML;
  use osCommerce\OM\Core\OSCOM;
  use osCommerce\OM\Core\Registry;

  use osCommerce\OM\Core\Site\Website\Partner;

  class Controller extends \osCommerce\OM\Core\Template\WidgetAbstract {
    static public function execute($param = null) {
      $OSCOM_MessageStack = Registry::get('MessageStack');
      $OSCOM_Template = Registry::get('Template');

      $partner = $OSCOM_Template->getValue('partner');

      $audit = Partner::getAudit($partner['code']);

      if ( !empty($audit) ) {
        $counter = 0;

        $output = '<ul class="nav nav-tabs">';

        foreach ( $audit as $a ) {
          if ( $counter < 3 ) {
            $output .= '<li><a href="#audit' . $counter . '" data-toggle="tab">' . $a['date_added'] . '</a></li>';
          } else {
            if ( $counter === 3 ) {
              $output .= '<li class="dropdown"><a class="dropdown-toggle" href="#" data-toggle="dropdown"><b class="caret"></b></a>
                            <ul class="dropdown-menu">';
            }

            $output .= '<li><a href="#audit' . $counter . '" data-toggle="tab">' . $a['date_added'] . '</a></li>';
          }

          $counter++;
        }

        if ( $counter >= 3 ) {
          $output .= '</ul></li>';
        }

        $output .= '</ul>';

        $counter = 0;

        $output .= '<div class="tab-content">';

        foreach ( $audit as $a ) {
          $output .= '<div class="tab-pane" id="audit' . $counter . '">
                        <p>by <a href="http://forums.oscommerce.com/index.php?showuser=' . $a['user_id'] . '">' . $a['user_name'] . '</a></p>';

          foreach ( $a['rows'] as $row ) {
            $output .= '<h4>' . $row['row_key'] . '</h4>
                        <span class="label label-important">Old</span>
                        <pre>' . HTML::outputProtected($row['old_value']) . '</pre>
                        <span class="label label-success">New</span>
                        <pre>' . HTML::outputProtected($row['new_value']) . '</pre>';
          }

          $output .= '</div>';

          $counter++;
        }

        $output .= '</div>';
      } else {
        $OSCOM_MessageStack->add('widget_partner_audit_log', OSCOM::getDef('partner_audit_log_empty'), 'warning');

        $output = $OSCOM_MessageStack->get('widget_partner_audit_log');
      }

      $OSCOM_Template->setValue('partner_audit_log', $output);

      $file = OSCOM::BASE_DIRECTORY . 'Custom/Site/' . OSCOM::getSite() . '/Application/' . OSCOM::getSiteApplication() . '/Module/Template/Widget/partner_audit_log/pages/main.html';

      if ( !file_exists($file) ) {
        $file = OSCOM::BASE_DIRECTORY . 'Core/Site/' . OSCOM::getSite() . '/Application/' . OSCOM::getSiteApplication() . '/Module/Template/Widget/partner_audit_log/pages/main.html';
      }

      return file_get_contents($file);
    }
  }
?>
