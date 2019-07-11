<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\_\Action;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    HTML,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Shop\Address;

use osCommerce\OM\Core\Site\Website\{
    Braintree,
    Users
};

class Ambassadors
{
    const COUNTRIES_WITH_ZONES = ['AU', 'CA', 'DE', 'US'];

    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_Currency = Registry::get('Currency');
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

        $OSCOM_Language->loadIniFile('pages/ambassadors.php');
        $OSCOM_Language->loadIniFile('pages/legal_tos_ambassador.php');

        $OSCOM_Template->setValue('public_token', $_SESSION[OSCOM::getSite()]['public_token']);
        $OSCOM_Template->setValue('recaptcha_key_public', OSCOM::getConfig('recaptcha_key_public'));

        $application->setPageContent('ambassadors.html');
        $application->setPageTitle(OSCOM::getDef('amb_html_page_title'));

        if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/highlights/ambassadors.jpg')) {
            $OSCOM_Template->setValue('highlights_image', 'images/highlights/ambassadors.jpg');
        }

        $OSCOM_Template->setValue('language_code', $OSCOM_Language->getCode());
        $OSCOM_Template->setValue('amb_price', $OSCOM_Currency->trim($OSCOM_Currency->show(Users::AMBASSADOR_LEVEL_PRICE)));
        $OSCOM_Template->setValue('amb_prices', $OSCOM_Currency->showAll(Users::AMBASSADOR_LEVEL_PRICE, true));
        $OSCOM_Template->setValue('is_ambassador', (isset($_SESSION[OSCOM::getSite()]['Account']) && ($_SESSION[OSCOM::getSite()]['Account']['amb_level'] > 0)));
        $OSCOM_Template->setValue('ambassador_user_next_level', isset($_SESSION[OSCOM::getSite()]['Account']) ? $_SESSION[OSCOM::getSite()]['Account']['amb_level'] + 1 : 1);
        $OSCOM_Template->setValue('braintree_google_merchant_id', OSCOM::getConfig('braintree_google_merchant_id'));

        $OSCOM_Template->addHtmlElement('footer', '<script src="' . OSCOM::getPublicSiteLink('javascript/Application/_/Ambassadors.min.js') . '"></script>');

        $OSCOM_Template->addHtmlElement('footer', '<script src="' . OSCOM::getPublicSiteLink('external/js.cookie.min.js') . '"></script>');
        $OSCOM_Template->addHtmlElement('footer', '<script>Cookies.defaults = {path: OSCOM.cookie["path"], domain: OSCOM.cookie["domain"], secure: true};</script>');

        $amb_members = [];

        foreach (Users::getNewestAmbassadors(12) as $a) {
            $m = Users::get($a);

            $amb_members[] = [
                'name' => $m['name'],
                'profile_url' => $m['profile_url'],
                'photo_url' => $m['photo_url']
            ];
        }

        $OSCOM_Template->setValue('amb_members', $amb_members);

        $countries = [
            [
                'id' => '',
                'text' => OSCOM::getDef('select_please_select')
            ]
        ];

        foreach (Address::getCountries() as $c) {
            $countries[$c['id']] = [
                'id' => $c['iso_2'],
                'text' => $c['name']
            ];
        }

        $countries_field = HTML::selectMenu('country', $countries, null, 'id="cCountry" class="custom-select" required');

        $OSCOM_Template->setValue('field_countries', $countries_field);

        $zones = [];

        foreach (Address::getZones(static::COUNTRIES_WITH_ZONES) as $z) {
            $zones[$countries[$z['country_id']]['id']][] = [
                'code' => $z['code'],
                'title' => $z['name']
            ];
        }

        $OSCOM_Template->setValue('select_zones', $zones);

        if (isset($_SESSION[OSCOM::getSite()]['Account']) && Users::hasAddress($_SESSION[OSCOM::getSite()]['Account']['id'], 'billing')) {
            $address = Users::getAddress($_SESSION[OSCOM::getSite()]['Account']['id'], 'billing');
            $address = reset($address);

            $OSCOM_Template->setValue('billing_address', $address);
        }
    }
}
