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

class Info
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_Session = Registry::get('Session');
        $OSCOM_Template = Registry::get('Template');

        if (!$OSCOM_Session->hasStarted()) {
            $OSCOM_Session->start();
        }

        $req_sig = OSCOM::getSiteApplication() . '&' . $application->getCurrentAction();

        if (!isset($_SESSION[OSCOM::getSite()]['keepAlive']) || !in_array($req_sig, $_SESSION[OSCOM::getSite()]['keepAlive'])) {
            $_SESSION[OSCOM::getSite()]['keepAlive'][] = $req_sig;
        }

        $OSCOM_Template->setValue('public_token', $_SESSION[OSCOM::getSite()]['public_token']);

        if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $OSCOM_Template->setValue('recaptcha_key_public', OSCOM::getConfig('recaptcha_key_public'));
            $OSCOM_Template->addHtmlElement('header', '<script src="https://www.google.com/recaptcha/api.js?hl=' . $OSCOM_Language->getCode() . '"></script>');
        }

        $application->setPageContent('corporate_sponsorship.html');
        $application->setPageTitle(OSCOM::getDef('cs_html_page_title'));
    }
}
