<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\Action\Edit;

use osCommerce\OM\Core\{
    ApplicationAbstract,
    OSCOM,
    Registry,
    Sanitize
};

use osCommerce\OM\Core\Site\Website\{
    Invision,
    Users
};

class Process
{
    public static function execute(ApplicationAbstract $application)
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');
        $OSCOM_Template = Registry::get('Template');

        $error = false;

        $public_token = Sanitize::simple($_POST['public_token'] ?? null);

        if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
            $OSCOM_MessageStack->add('account', OSCOM::getDef('error_form_protect_general'), 'error');

            return false;
        }

        $uFullName = Sanitize::simple($_POST['name'] ?? null);
        $uBdayMonth = Sanitize::simple($_POST['bday_month'] ?? null);
        $uBdayDay = Sanitize::simple($_POST['bday_day'] ?? null);
        $uBdayYear = Sanitize::simple($_POST['bday_year'] ?? null);
        $uGender = Sanitize::simple($_POST['gender'] ?? null);
        $uLocation = Sanitize::simple($_POST['location'] ?? null);
        $uCompany = Sanitize::simple($_POST['company'] ?? null);
        $uWebsite = Sanitize::simple($_POST['website'] ?? null);
        $uBioShort = Sanitize::para($_POST['bio_short'] ?? null);

        if (isset($_FILES['user_photo']) && ($_FILES['user_photo']['error'] == \UPLOAD_ERR_OK) && is_uploaded_file($_FILES['user_photo']['tmp_name'])) {
            if ($_FILES['user_photo']['size'] > Invision::MAX_PROFILE_PHOTO_FILESIZE) {
                $error = true;

                $OSCOM_MessageStack->add('account', OSCOM::getDef('edit_error_photo_filesize_big'), 'error');
            } else {
                $fileinfo = finfo_open(\FILEINFO_MIME_TYPE);

                if (!in_array(finfo_file($fileinfo, $_FILES['user_photo']['tmp_name']), ['image/jpg', 'image/jpeg', 'image/png','image/gif'])) {
                    $error = true;

                    $OSCOM_MessageStack->add('account', OSCOM::getDef('edit_error_photo_filetype_invalid'), 'error');
                }
            }
        }

        if (is_numeric($uBdayMonth) && is_numeric($uBdayDay) && is_numeric($uBdayYear) && ($uBdayYear <= date('Y')) && ($uBdayYear >= (date('Y') - 150))) {
            if (!checkdate($uBdayMonth, $uBdayDay, $uBdayYear)) {
                $error = true;

                $OSCOM_MessageStack->add('account', OSCOM::getDef('edit_error_birthday_invalid'), 'error');
            }
        } else {
            $uBdayYear = null;

            if (is_numeric($uBdayMonth) && ($uBdayMonth >= 1) && ($uBdayMonth <= 12) && is_numeric($uBdayDay) && ($uBdayDay >= 1) && ($uBdayDay <= 31)) {
                $birthday_date = explode('/', (\DateTime::createFromFormat('n/j', $uBdayMonth . '/' . $uBdayDay))->format('m/d'), 2);

                $uBdayMonth = $birthday_date[0];
                $uBdayDay = $birthday_date[1];
            } else {
                $uBdayMonth = $uBdayDay = null;
            }
        }

        if (!in_array($uGender, [
            '',
            'male',
            'female',
            'other'
        ])) {
            $error = true;

            $OSCOM_MessageStack->add('account', OSCOM::getDef('edit_error_gender_invalid'), 'error');
        }

        if (!empty($uWebsite)) {
            $url_pass = true;

            if ((mb_strpos($uWebsite, '.') === false) || ((mb_strpos($uWebsite, '://') !== false) && (preg_match('/^(http|https)\:\/\/.+/', $uWebsite) !== 1))) {
                $url_pass = false;
            }

            if ($url_pass === true) {
                $url_filtered = $uWebsite;

// international domains (eg, containing german umlauts) are converted to punycode
                if (mb_detect_encoding($url_filtered, 'ASCII', true) !== 'ASCII') {
                    $url_filtered = idn_to_ascii($url_filtered);
                }

                if (mb_strpos($uWebsite, '://') === false) {
                    $url_filtered = 'https://' . $url_filtered;
                }

                if (filter_var($url_filtered, FILTER_VALIDATE_URL) === false) {
                    $url_pass = false;
                }
            }

            if ($url_pass === false) {
                $error = true;

                $OSCOM_MessageStack->add('account', OSCOM::getDef('edit_error_website_invalid'), 'error');
            }
        }

        if ($error === false) {
            switch ($uGender) {
                case 'male':
                    $uGender = 'Male';
                    break;

                case 'female':
                    $uGender = 'Female';
                    break;

                case 'other':
                    $uGender = 'Not Telling';
                    break;
            }

            $data = [
                'birthday' => $uBdayMonth ? ($uBdayMonth . '/' . $uBdayDay . ($uBdayYear ? '/' . $uBdayYear : '')) : null,
                'customFields' => [
                    Invision::CUSTOM_FIELDS['full_name']['id'] => $uFullName,
                    Invision::CUSTOM_FIELDS['gender']['id'] => $uGender,
                    Invision::CUSTOM_FIELDS['location']['id'] => $uLocation,
                    Invision::CUSTOM_FIELDS['company']['id'] => $uCompany,
                    Invision::CUSTOM_FIELDS['website']['id'] => $uWebsite,
                    Invision::CUSTOM_FIELDS['bio_short']['id'] => mb_substr($uBioShort, 0, 300)
                ]
            ];

            if (isset($_FILES['user_photo']) && ($_FILES['user_photo']['error'] == \UPLOAD_ERR_OK) && is_uploaded_file($_FILES['user_photo']['tmp_name'])) {
                $data['profilePhoto'] = $_FILES['user_photo']['tmp_name'];
            }

            if (Users::save($_SESSION[OSCOM::getSite()]['Account']['id'], $data)) {
                $OSCOM_MessageStack->add('account', OSCOM::getDef('edit_success_saved'), 'success');
            }

            OSCOM::redirect(OSCOM::getLink(null, 'Account', 'SSL'));
        }
    }
}
