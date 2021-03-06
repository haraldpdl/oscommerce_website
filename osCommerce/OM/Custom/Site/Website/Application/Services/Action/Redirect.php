<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Services\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Website\Partner;

class Redirect
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Template = Registry::get('Template');

        $partner_title = null;
        $partner_url = null;

        if (!empty($_GET['Redirect'])) {
            $partner = Partner::get($_GET['Redirect']);

            if (!empty($partner)) {
                $partner_title = $partner['title'];
                $partner_url = $partner['url'];

                if (isset($_GET['url']) && !empty($_GET['url']) && (strlen($_GET['url']) == 8)) {
                    $link_url = Partner::getStatusUpdateUrl($_GET['Redirect'], $_GET['url']);

                    if (!empty($link_url)) {
                        $partner_url = $link_url;
                    }
                }
            }
        }

        if (!isset($partner_url) || empty($partner_url)) {
            OSCOM::redirect(OSCOM::getLink('Website', 'Services'));
        }

        $OSCOM_Template->addHtmlElement('header', '<meta name="robots" content="noindex, nofollow">');

        $OSCOM_Template->setValue('partner_title', $partner_title);
        $OSCOM_Template->setValue('partner_url', $partner_url);
        $OSCOM_Template->setValue('partner_url_js', json_encode($partner_url));

        $application->setPageContent('redirect.html');
        $application->setPageTitle(OSCOM::getDef('redirect_html_page_title', [':partner_title' => $partner_title]));
    }
}
