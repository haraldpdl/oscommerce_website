<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2017 osCommerce; https://www.oscommerce.com
 * @license BSD; https://www.oscommerce.com/license/bsd.txt
 */

namespace osCommerce\OM\Core\Site\Website\Scripts\GenerateInvoices;

use osCommerce\OM\Core\{
    Mail,
    OSCOM,
    Registry,
    TransactionId
};

use osCommerce\OM\Core\Site\Shop\Address;

use osCommerce\OM\Core\Site\Website\{
    Invoices,
    Users
};

use Dompdf\Dompdf;

define('OSCOM_TIMESTAMP_START', microtime());

error_reporting(E_ALL | E_STRICT);

if (strpos(__FILE__, '/mnt/') !== false) {
    $oscom_root_path = '/mnt/c/Users/Harald Ponce de Leon/Projects/osCommerce/haraldpdl/online_merchant/';
} else {
    $oscom_root_path = '/home/hazza/Projects/haraldpdl/oscommerce/';
}

define('OSCOM_PUBLIC_BASE_DIRECTORY', $oscom_root_path);

require(OSCOM_PUBLIC_BASE_DIRECTORY . 'osCommerce/OM/Core/OSCOM.php');
spl_autoload_register('osCommerce\\OM\\Core\\OSCOM::autoload');

OSCOM::setSite('Website');
OSCOM::initialize();

$lockfile = OSCOM::BASE_DIRECTORY . 'Work/Temp/' . basename(__FILE__) . '.lockfile';

if (is_file($lockfile)) {
    trigger_error('SCRIPT ' . basename(__FILE__, '.php') . ': Lockfile exists: ' . $lockfile);
    exit;
}

if (!touch($lockfile)) {
    trigger_error('SCRIPT ' . basename(__FILE__, '.php') . ': Can\'t set lockfile: ' . $lockfile);
    exit;
}

set_time_limit(0);

require(OSCOM::BASE_DIRECTORY . 'Custom/Site/Website/External/dompdf/autoload.inc.php');

$OSCOM_Language = Registry::get('Language');
$OSCOM_Template = Registry::get('Template');

$OSCOM_Language->loadIniFileFromDirectory(__DIR__ . '/languages', 'invoice.php');
$OSCOM_Language->loadIniFileFromDirectory(__DIR__ . '/languages', 'email.php');

$modules = [];

foreach (Invoices::getNew() as $i) {
    try {
        if (isset($i['module']) && !empty($i['module']) && !in_array($i['module'], $modules)) {
            $modules[] = $i['module'];
        }

        $user = Users::get($i['user_id']);

        $invoice_number_formatted = str_pad(TransactionId::get('inv'), 10, '0', STR_PAD_LEFT);

        $address = json_decode($i['billing_address'], true);

        $billing_address = Address::format([
            'company' => $address['company'],
            'firstname' => $address['firstname'],
            'lastname' => $address['lastname'],
            'state' => $address['state'],
            'zone_code' => $address['zone_code'],
            'country_title' => '',
            'country_id' => Address::getCountryId($address['country_iso_2']),
            'street_address' => $address['street'],
            'street_address_2' => $address['street2'],
            'city' => $address['city'],
            'postcode' => $address['zip']
        ], '<br>');

        $purchase_items = json_decode($i['purchase_items'], true);

        foreach ($purchase_items as $pik => &$piv) {
            $piv['cost_formatted'] = $OSCOM_Language->formatNumber($piv['cost'], 2) . ' €';
        }

        $order_total_items = json_decode($i['order_total_items'], true);
        $ot_items = [];

        foreach ($order_total_items as $otk => $otv) {
            if ($otk == 'tax') {
                foreach ($otv as $ott) {
                    $ot_items[] = [
                        'title' => $ott['title'],
                        'cost' => $ott['cost'],
                        'cost_formatted' => $OSCOM_Language->formatNumber($ott['cost'], 2) . ' €'
                    ];
                }
            } else {
                $ot_items[] = [
                    'title' => $otv['title'],
                    'cost' => $otv['cost'],
                    'cost_formatted' => $OSCOM_Language->formatNumber($otv['cost'], 2) . ' €'
                ];
            }
        }

        $DATE_now = new \DateTime();
        $DATE_purchased = \DateTime::createFromFormat('Y-m-d H:i:s', $i['date_added']);

        $OSCOM_Template->setValue('billing_address', $billing_address, true);
        $OSCOM_Template->setValue('invoice_number', $invoice_number_formatted, true);
        $OSCOM_Template->setValue('invoice_date', $DATE_now->format('j. F Y'), true);
        $OSCOM_Template->setValue('invoice_items', $purchase_items, true);
        $OSCOM_Template->setValue('invoice_totals', $ot_items, true);
        $OSCOM_Template->setValue('purchase_date', $DATE_purchased->format('j. F Y'), true);

        $content = $OSCOM_Template->getContent(__DIR__ . '/pages/invoice.html');

        $dompdf = new Dompdf();
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->set_option('isFontSubsettingEnabled', true);
        $dompdf->set_option('fontHeightRatio', 0.9);

        $dompdf->loadHtml($content);
        $dompdf->render();

        $pdf = $dompdf->output();
        file_put_contents(OSCOM::getConfig('dir_fs_invoices') . $invoice_number_formatted . '.pdf', $pdf);

        Invoices::save([
            'id' => $i['id'],
            'invoice_number' => $invoice_number_formatted,
            'status' => Invoices::STATUS_PAID
        ]);

        Invoices::saveUser([
            'invoice_number' => $invoice_number_formatted,
            'date' => $DATE_now->format('Y-m-d H:i:s'),
            'title' => $i['title'],
            'cost' => $i['cost'],
            'currency_id' => $i['currency_id'],
            'status' => Invoices::STATUS_PAID,
            'user_id' => $i['user_id'],
            'partner_transaction_id' => $i['partner_transaction_id']
        ]);

        if (isset($i['module']) && !empty($i['module'])) {
            if (class_exists('osCommerce\\OM\\Core\\Site\\Website\\Scripts\\GenerateInvoices\\Module\\' . $i['module'])) {
                call_user_func([
                    'osCommerce\\OM\\Core\\Site\\Website\\Scripts\\GenerateInvoices\\Module\\' . $i['module'],
                    'beforeMail'
                ], $user, $i);
            }
        }

        $OSCOM_Template->setValue('user_name', $user['name'], true);

        $email_txt = $OSCOM_Template->getContent(__DIR__ . '/pages/email.txt');
        $email_html = $OSCOM_Template->getContent(__DIR__ . '/pages/email.html');

        if (!empty($email_txt) || !empty($email_html)) {
            $OSCOM_Mail = new Mail($user['name'], $user['email'], 'osCommerce', 'noreply@oscommerce.com', OSCOM::getDef('invoice_email_title'));

            if (!empty($email_txt)) {
                $OSCOM_Mail->setBodyPlain($email_txt);
            }

            if (!empty($email_html)) {
                $OSCOM_Mail->setBodyHTML($email_html);
            }

            $OSCOM_Mail->send();

            $OSCOM_Mail = new Mail('Harald Ponce de Leon', 'hpdl@oscommerce.com', 'osCommerce', 'noreply@oscommerce.com', OSCOM::getDef('invoice_email_title'));

            if (!empty($email_txt)) {
                $OSCOM_Mail->setBodyPlain($email_txt);
            }

            if (!empty($email_html)) {
                $OSCOM_Mail->setBodyHTML($email_html);
            }

            $OSCOM_Mail->send();
        }
    } catch (\Exception $e) {
        trigger_error('SCRIPT ' . basename(__FILE__, '.php') . ': ' . $e->getMessage());
        exit;
    }
}

foreach ($modules as $m) {
    if (class_exists('osCommerce\\OM\\Core\\Site\\Website\\Scripts\\GenerateInvoices\\Module\\' . $m)) {
        call_user_func([
            'osCommerce\\OM\\Core\\Site\\Website\\Scripts\\GenerateInvoices\\Module\\' . $m,
            'cleanup'
        ]);
    }
}

unlink($lockfile);
