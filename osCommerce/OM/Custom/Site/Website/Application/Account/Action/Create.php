<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/bsdlicense.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM,
    Registry
};

class Create
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_Template = Registry::get('Template');

        if (isset($_SESSION[OSCOM::getSite()]['Account'])) {
            OSCOM::redirect(OSCOM::getLink(null, 'Account', null, 'SSL'));
        }

        if (isset($_GET['return_forum'])) {
            $_SESSION['login_redirect'] = [
                'url' => 'https://forums.oscommerce.com',
                'cancel_url' => 'https://forums.oscommerce.com',
                'cancel_text' => OSCOM::getDef('create_return_to_forum')
            ];
        }

        if (isset($_SESSION['login_redirect']) && isset($_SESSION['login_redirect']['cancel_url']) && isset($_SESSION['login_redirect']['cancel_text'])) {
            $OSCOM_Template->setValue('login_redirect_cancel_text', $_SESSION['login_redirect']['cancel_text']);
        }

        $application->setPageContent('create.html');
        $application->setPageTitle(OSCOM::getDef('create_html_title'));

        $OSCOM_Template->addHtmlElement('header', '<script src="https://www.google.com/recaptcha/api.js?hl=' . $OSCOM_Language->getCode() . '"></script>');
    }
}
