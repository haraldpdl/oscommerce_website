<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
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

use Cocur\Slugify\Slugify;

class Ambassadors
{
    const COUNTRIES_WITH_ZONES = ['AU', 'CA', 'DE', 'US'];

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

        $OSCOM_Language->loadIniFile('pages/ambassadors.php');

        $OSCOM_Template->setValue('public_token', $_SESSION[OSCOM::getSite()]['public_token']);
        $OSCOM_Template->setValue('recaptcha_key_public', OSCOM::getConfig('recaptcha_key_public'));
        $OSCOM_Template->addHtmlElement('footer', '<script src="https://www.google.com/recaptcha/api.js?hl=' . $OSCOM_Language->getCode() . '&render=explicit" async defer></script>');

        $application->setPageContent('ambassadors.html');
        $application->setPageTitle(OSCOM::getDef('amb_html_page_title'));

        if (file_exists(OSCOM::getConfig('dir_fs_public', 'OSCOM') . 'sites/' . OSCOM::getSite() . '/images/highlights_ambassadors.jpg')) {
            $OSCOM_Template->setValue('highlights_image', 'images/highlights_ambassadors.jpg');
        }

        $OSCOM_Template->setValue('is_ambassador', (isset($_SESSION[OSCOM::getSite()]['Account']) && ($_SESSION[OSCOM::getSite()]['Account']['amb_level'] > 0)));
        $OSCOM_Template->setValue('ambassador_user_next_level', isset($_SESSION[OSCOM::getSite()]['Account']) ? $_SESSION[OSCOM::getSite()]['Account']['amb_level'] + 1 : 1);

        $OSCOM_Template->addHtmlElement('footer', '<script src="https://js.braintreegateway.com/web/dropin/' . Braintree::WEB_DROPIN_VERSION . '/js/dropin.min.js"></script><script src="https://js.braintreegateway.com/web/' . Braintree::WEB_VERSION . '/js/client.min.js"></script><script src="https://js.braintreegateway.com/web/' . Braintree::WEB_VERSION . '/js/three-d-secure.min.js"></script>');

        $slugify = new Slugify();

        $amb_members = [];

        foreach (Users::getNewestAmbassadors(12) as $a) {
            $m = Users::get($a);

            $amb_members[] = [
                'id' => $m['id'],
                'name' => $m['name'],
                'name_slug' => $slugify->slugify($m['name']),
                'photo' => $m['photo_url']
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

        $countries_field = HTML::selectMenu('country', $countries, null, 'id="cCountry" class="form-control"');

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
