<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Partner\Sites;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM,
    Registry,
    Sanitize
};

use osCommerce\OM\Core\Site\Sites\Sites;

class Process
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        $public_token = Sanitize::simple($_POST['public_token'] ?? null);
        $public_id = Sanitize::simple($_POST['public_id'] ?? null);
        $action = Sanitize::simple($_POST['action'] ?? null);

        if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('error_form_protect_general'), 'error');

            return false;
        }

        $partner_campaign = $OSCOM_Template->getValue('partner_campaign');
        $partner_showcase_total = $OSCOM_Template->getValue('partner_showcase_total');
        $partner_showcase_max = $OSCOM_Template->getValue('partner_showcase_max');

        if (empty($public_id)) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_site_nonexistent'), 'error');

            return false;
        }

        if (!in_array($action, ['add', 'remove'])) {
            $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_site_action_unknown'), 'error');

            return false;
        }

        switch ($action) {
            case 'add':
                $pass = false;

                foreach ($OSCOM_Template->getValue('partner_sites') as $site) {
                    if ($site['public_id'] == $public_id) {
                        $pass = true;
                        break;
                    }
                }

                if ($pass === false) {
                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_site_nonexistent'), 'error');

                    return false;
                }

                foreach ($OSCOM_Template->getValue('partner_showcase') as $site) {
                    if ($site['public_id'] == $public_id) {
                        $pass = false;
                        break;
                    }
                }

                if ($pass === false) {
                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_site_already_showcase'), 'error');

                    return false;
                }

                if ((int)$partner_showcase_total >= (int)$partner_showcase_max) {
                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_max_site_showcase'), 'error');

                    return false;
                }

                if (Sites::saveShowcase($public_id, $_GET['Sites'], $_SESSION['Website']['Account']['id']) !== true) {
                    $pass = false;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_site_showcase_add'), 'error');

                    return false;
                }

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_success_site_showcase_add'), 'success');

                break;

            case 'remove':
                $pass = false;

                foreach ($OSCOM_Template->getValue('partner_showcase') as $site) {
                    if ($site['public_id'] == $public_id) {
                        $pass = true;
                        break;
                    }
                }

                if ($pass === false) {
                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_site_showcase_nonexistent'), 'error');

                    return false;
                }

                if (Sites::deleteShowcase($public_id, $_GET['Sites']) !== true) {
                    $pass = false;

                    $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_error_site_showcase_remove'), 'error');

                    return false;
                }

                $OSCOM_MessageStack->add('partner', OSCOM::getDef('partner_success_site_showcase_remove'), 'success');

                break;
        }

        OSCOM::redirect(OSCOM::getLink(null, null, 'Partner&Sites=' . $_GET['Sites'], 'SSL'));
    }
}
