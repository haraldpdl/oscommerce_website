<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Module\Template\Widget\partner_audit_log;

use osCommerce\OM\Core\{
  HTML,
  OSCOM,
  Registry
};

use osCommerce\OM\Core\Site\Website\Partner;

class Controller extends \osCommerce\OM\Core\Template\WidgetAbstract
{
    public static function execute($param = null)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        $partner = $OSCOM_Template->getValue('partner');

        $audit = Partner::getAudit($partner['code']);

        if (!empty($audit)) {
            $counter = 0;

            $output = '<ul class="nav nav-tabs" role="tablist">';

            foreach ($audit as $a) {
                if ($counter < 3) {
                    $output .= '<li class="nav-item"><a href="#audit' . $counter . '" id="haudit' . $counter . '" class="nav-link' . ($counter === 0 ? ' active' : '') . '" data-toggle="tab" role="tab" aria-controls="audit' . $counter . '" aria-selected="false">' . $a['date_added'] . '</a></li>';
                } else {
                    if ($counter === 3) {
                        $output .= '<li class="nav-item dropdown"><a href="#" id="auditMoreMenu" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"></a>
                                    <div class="dropdown-menu" aria-labelledby="auditMoreMenu">';
                    }

                    $output .= '<a href="#audit' . $counter . '" class="dropdown-item" data-toggle="tab">' . $a['date_added'] . '</a>';
                }

                $counter++;
            }

            if ($counter >= 3) {
                $output .= '</div></li>';
            }

            $output .= '</ul>';

            $counter = 0;

            $output .= '<div class="tab-content">';

            foreach ($audit as $a) {
                $output .= '<div class="tab-pane' . ($counter === 0 ? ' show active' : '') . '" id="audit' . $counter . '" role="tabpanel" aria-labelledby="haudit' . $counter . '">
                              <p class="small text-muted">' . OSCOM::getDef('audit_by', [':user_id' => $a['user_id'], ':user_name' => $a['user_name']]) . '</p>';

                foreach ($a['rows'] as $row) {
                    $output .= '<h3>' . $row['row_key'] . '</h3>';

                    if (!empty($row['old_value'])) {
                        $output .= '<div class="alert alert-danger">
                                      <h4 class="alert-heading">' . OSCOM::getDef('audit_old') . '</h4>
                                      <pre class="prettyCode pre-scrollable">' . HTML::outputProtected($row['old_value']) . '</pre>
                                    </div>';
                    }

                    $output .= '<div class="alert alert-success">
                                  <h4 class="alert-heading">' . OSCOM::getDef('audit_new') . '</h4>
                                  <pre class="prettyCode pre-scrollable">' . HTML::outputProtected($row['new_value']) . '</pre>
                                </div>';
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

        if (!file_exists($file)) {
            $file = OSCOM::BASE_DIRECTORY . 'Core/Site/' . OSCOM::getSite() . '/Application/' . OSCOM::getSiteApplication() . '/Module/Template/Widget/partner_audit_log/pages/main.html';
        }

        return file_get_contents($file);
    }
}
