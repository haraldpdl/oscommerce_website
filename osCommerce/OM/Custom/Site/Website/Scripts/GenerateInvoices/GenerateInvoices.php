<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Scripts\GenerateInvoices;

use osCommerce\OM\Core\{
    Cache,
    HTML,
    Mail,
    OSCOM,
    Registry,
    RunScript,
    TransactionId
};

use osCommerce\OM\Core\Site\Shop\Address;

use osCommerce\OM\Core\Site\Website\{
    Invoices,
    Users
};

class GenerateInvoices implements \osCommerce\OM\Core\RunScriptInterface
{
    const COUNTRIES_WITH_ZONES = ['AU', 'CA', 'DE', 'US'];

    public static function execute()
    {
        OSCOM::initialize('Website');

        $OSCOM_Currency = Registry::get('Currency');
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

                $invoice_number_formatted = str_pad((string)TransactionId::get('inv'), 10, '0', STR_PAD_LEFT);

                $address = json_decode($i['billing_address'], true);

                if (empty($address['state']) && isset($address['zone_code']) && isset($address['country_id']) && !in_array(Address::getCountryIsoCode2($address['country_id']), static::COUNTRIES_WITH_ZONES)) {
                    $address['state'] = Address::getZoneName($address['zone_id']);
                }

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

                if (!empty($address['vat_id'])) {
                    $vatidbr = Address::getVatIdTitleAbr(Address::getCountryId($address['country_iso_2']));

                    if (!empty($vatidbr)) {
                        $billing_address .= '<br><br>' . HTML::outputProtected($vatidbr) . ': ' . HTML::outputProtected($address['vat_id']);
                    }
                }

                $purchase_items = json_decode($i['purchase_items'], true);

                foreach ($purchase_items as $pik => &$piv) {
                    $piv['cost_formatted'] = $OSCOM_Currency->show($piv['cost'], $OSCOM_Currency->getCode($i['currency_id']), null, false);
                }

                $order_total_items = json_decode($i['order_total_items'], true);
                $ot_items = [];

                foreach ($order_total_items as $otk => $otv) {
                    if ($otk == 'tax') {
                        foreach ($otv as $ott) {
                            $ot_items[] = [
                                'title' => $ott['title'],
                                'cost' => $ott['cost'],
                                'cost_formatted' => $OSCOM_Currency->show($ott['cost'], $OSCOM_Currency->getCode($i['currency_id']), null, false)
                            ];
                        }
                    } else {
                        $ot_items[] = [
                            'title' => $otv['title'],
                            'cost' => $otv['cost'],
                            'cost_formatted' => $OSCOM_Currency->show($otv['cost'], $OSCOM_Currency->getCode($i['currency_id']), null, false)
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
                $OSCOM_Template->setValue('purchase_date', ($DATE_purchased !== false) ? $DATE_purchased->format('j. F Y') : '', true);
                $OSCOM_Template->setValue('invoice_currency', $OSCOM_Currency->get('title', $OSCOM_Currency->getCode($i['currency_id'])), true);

                $content = $OSCOM_Template->getContent(__DIR__ . '/pages/invoice.html');

                $dompdf = new \Dompdf\Dompdf(new \Dompdf\Options([
                    'isRemoteEnabled' => true,
                    'isFontSubsettingEnabled' => true,
                    'fontHeightRatio' => 0.9
                ]));

                $dompdf->setPaper('a4', 'portrait');

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

                Cache::clear('users-' . $i['user_id'] . '-invoices-check');
                Cache::clear('users-' . $i['user_id'] . '-invoices');

                if (isset($i['module']) && !empty($i['module'])) {
                    $class = 'osCommerce\\OM\\Core\\Site\\Website\\Scripts\\GenerateInvoices\\Module\\' . $i['module'];

                    if (class_exists($class)) {
                        $callable = [
                            $class,
                            'beforeMail'
                        ];

                        if (is_callable($callable)) {
                            call_user_func($callable, $user, $i);
                        }
                    }
                }

                $OSCOM_Template->setValue('user_name', $user['name'], true);

                $email_txt = $OSCOM_Template->getContent(__DIR__ . '/pages/email.txt');
                $email_html = $OSCOM_Template->getContent(__DIR__ . '/pages/email.html');

                if (!empty($email_txt) || !empty($email_html)) {
                    $OSCOM_Mail = new Mail($user['email'], $user['name'], 'sales@oscommerce.com', 'osCommerce', OSCOM::getDef('invoice_email_title'));

                    $OSCOM_Mail->addBCC('hpdl@oscommerce.com', 'Harald Ponce de Leon');

                    if (!empty($email_txt)) {
                        $OSCOM_Mail->setBodyPlain($email_txt);
                    }

                    if (!empty($email_html)) {
                        $OSCOM_Mail->setBodyHTML($email_html);
                    }

                    $OSCOM_Mail->send();
                }
            } catch (\Exception $e) {
                RunScript::error('(GenerateInvoices) ' . $e->getMessage());
            }
        }

        foreach ($modules as $m) {
            $class = 'osCommerce\\OM\\Core\\Site\\Website\\Scripts\\GenerateInvoices\\Module\\' . $m;

            if (class_exists($class)) {
                $callable = [
                    $class,
                    'cleanup'
                ];

                if (is_callable($callable)) {
                    call_user_func($callable);
                }
            }
        }
    }
}
