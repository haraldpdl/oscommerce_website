<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Contact\RPC;

use osCommerce\OM\Core\{
    HttpRequest,
    OSCOM,
    Registry,
    Sanitize
};

use osCommerce\OM\Core\Site\Website\{
    ContactInquiry,
    Invision
};

use osCommerce\OM\Core\Site\RPC\Controller as RPC;

class SaveInquiry
{
    public static function execute()
    {
        $OSCOM_Session = Registry::get('Session');

        if (!$OSCOM_Session->hasStarted()) {
            $OSCOM_Session->start();
        }

        $result = [
            'rpcStatus' => RPC::STATUS_ERROR,
            'resetGSecurityCheck' => false
        ];

        $errors = [];

        $public_token = Sanitize::simple($_POST['public_token'] ?? null);
        $company = Sanitize::simple($_POST['company'] ?? null);
        $name = $_SESSION[OSCOM::getSite()]['Account']['name'] ?? Sanitize::simple($_POST['name'] ?? null);
        $email = $_SESSION[OSCOM::getSite()]['Account']['email'] ?? Sanitize::simple($_POST['email'] ?? null);
        $inquiry = Sanitize::simple($_POST['inquiry'] ?? null);
        $department = Sanitize::simple($_POST['department'] ?? null);
        $grSecurityCheck = $_POST['gr_security_check'] ?? '';

        if ($public_token !== md5($_SESSION[OSCOM::getSite()]['public_token'])) {
            $errors[] = OSCOM::getDef('error_form_protect_general');
        }

        if (empty($errors)) {
            if (!isset($_SESSION[OSCOM::getSite()]['Account']) && empty($name)) {
                $errors[] = OSCOM::getDef('contact_inquiry_ms_error_name_required');
            }

            if (!isset($_SESSION[OSCOM::getSite()]['Account']) && (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
                $errors[] = OSCOM::getDef('contact_inquiry_ms_error_email_invalid');
            }

            if (empty($inquiry)) {
                $errors[] = OSCOM::getDef('contact_inquiry_ms_error_inquiry_required');
            }
        }

        if (empty($errors)) {
            if (Invision::isFilterBanned(OSCOM::getIPAddress(), 'ip')) {
                $errors[] = OSCOM::getDef('contact_inquiry_ms_error_filter_unallowed');
            }
        }

        if (empty($errors)) {
            if (!isset($_SESSION[OSCOM::getSite()]['Account']) && Invision::isFilterBanned($email, 'email')) {
                $errors[] = OSCOM::getDef('contact_inquiry_ms_error_filter_unallowed');
            }
        }

        if (empty($errors) && !isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $recaptcha_error = true;

            if (!empty($grSecurityCheck)) {
                $params = [
                    'secret' => OSCOM::getConfig('recaptcha_key_private'),
                    'remoteip' => OSCOM::getIPAddress(),
                    'response' => $grSecurityCheck
                ];

                $post_string = '';

                foreach ($params as $key => $value) {
                    $post_string .= $key . '=' . urlencode(utf8_encode(trim($value))) . '&';
                }

                $post_string = substr($post_string, 0, -1);

                $response = HttpRequest::getResponse([
                    'url' => 'https://www.google.com/recaptcha/api/siteverify',
                    'parameters' => $post_string
                ]);

                if (!empty($response)) {
                    $gr_result = json_decode($response, true);

                    if (is_array($gr_result) && isset($gr_result['success']) && ($gr_result['success'] === true)) {
                        $recaptcha_error = false;
                    } else {
                        trigger_error('Contact Inquiry failed (' . $name . ' [' . $email . ']); Recaptcha: ' . $response);
                    }
                }

                $result['resetGSecurityCheck'] = true;
            }

            if ($recaptcha_error === true) {
                $errors[] = OSCOM::getDef('contact_inquiry_ms_error_security_check_invalid');

                $result['resetGSecurityCheck'] = true;
            }
        }

        if (empty($errors)) {
            if (!isset($department) || !ContactInquiry::departmentExists($department)) {
                $errors[] = OSCOM::getDef('contact_inquiry_ms_error_department_unknown');
            }
        }

        if (empty($errors)) {
            $data = [
                'company' => $company,
                'name' => $name,
                'email' => $email,
                'inquiry' => mb_substr($inquiry, 0, 2000),
                'department' => $department
            ];

            if (ContactInquiry::canSend($department) && (($inquiry_id = ContactInquiry::save($data)) !== null)) {
                $result['rpcStatus'] = RPC::STATUS_SUCCESS;
                $result['inquiryId'] = $inquiry_id;
                $result['inquiryName'] = $name;
                $result['inquiryEmail'] = $email;
            } else {
                $errors[] = OSCOM::getDef('contact_inquiry_ms_error_general');
            }
        }

        if (!empty($errors)) {
            $result['errors'] = $errors;
        }

        echo json_encode($result);
    }
}
