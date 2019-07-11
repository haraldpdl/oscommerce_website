<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Application\Account\RPC;

use osCommerce\OM\Core\{
    Cache,
    Mail,
    OSCOM,
    Registry
};

use osCommerce\OM\Core\Site\Shop\Address;

use osCommerce\OM\Core\Site\Website\{
    Braintree,
    BraintreeException,
    Invoices,
    Partner,
    Users
};

use osCommerce\OM\Core\Site\RPC\{
    Controller as RPC,
    Exception as RPCException
};

class ProcessBraintree
{
    public static function execute()
    {
        $OSCOM_Currency = Registry::get('Currency');
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_PDO = Registry::get('PDO');
        $OSCOM_Template = Registry::get('Template');

        $result = [];

        try {
            if (!isset($_SESSION[OSCOM::getSite()]['Account'])) {
                throw new RPCException(RPC::STATUS_NO_ACCESS);
            }

            if (!isset($_GET['p']) || empty($_GET['p']) || !Partner::hasCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['p'])) {
                throw new RPCException(RPC::STATUS_NO_ACCESS);
            }

            if (!isset($_GET['c']) || empty($_GET['c']) || !$OSCOM_Currency->exists($_GET['c'])) {
                throw new RPCException(RPC::STATUS_NO_ACCESS);
            }

            $OSCOM_Currency->setSelected($_GET['c']);

            $partner_campaign = Partner::getCampaign($_SESSION[OSCOM::getSite()]['Account']['id'], $_GET['p']);

            if ((int)$partner_campaign['billing_country_id'] < 1) {
                throw new RPCException(RPC::STATUS_NO_ACCESS);
            }

            $partner_billing_address = json_decode($partner_campaign['billing_address'], true);

            if (!is_array($partner_billing_address) || empty($partner_billing_address['street_address'])) {
                throw new RPCException(RPC::STATUS_NO_ACCESS);
            }

            $partner = Partner::get($_GET['p']);
            $packages = Partner::getPackages($partner['code']);

            $plan = $_POST['plan'] ?? null;
            $level_id = $_POST['duration'] ?? null;

            if (!isset($packages[$plan]) || !isset($packages[$plan]['levels'][$level_id])) {
                throw new RPCException(RPC::STATUS_NO_ACCESS);
            }

            $date_campaign = new \DateTime($partner_campaign['date_end']);

            $date_now = new \DateTime();

            $date_start = ($date_campaign < $date_now) ? $date_now : $date_campaign;

            $date_end = clone $date_start;
            $date_end->add(date_interval_create_from_date_string($packages[$plan]['levels'][$level_id]['duration'] . ' months'));

            $items = [
                [
                    'title' => OSCOM::getDef('cs_purchase_item', [
                        ':title' => $partner['title'],
                        ':period' => $date_start->format('jS M, Y') . ' - ' . $date_end->format('jS M, Y')
                    ]),
                    'cost' => $packages[$plan]['levels'][$level_id]['price_raw']
                ]
            ];

            $totals = [];

            if (isset($packages[$plan]['levels'][$level_id]['tax'])) {
                $tax_raw = $packages[$plan]['levels'][$level_id]['tax_raw'];

                $items[0]['tax'] = $tax_raw;

                $tax_code = array_key_first($tax_raw);

                $totals['tax'] = [
                    $tax_code => [
                        'title' => OSCOM::getDef('purchase_tax_' . $tax_code . '_title'),
                        'cost' => $tax_raw[$tax_code]
                    ]
                ];
            }

            $totals['total'] = [
                'title' => OSCOM::getDef('cs_purchase_total'),
                'cost' => $packages[$plan]['levels'][$level_id]['total_raw']
            ];

            $data = [
                'paymentMethodNonce' => $_POST['nonce'],
                'amount' => $totals['total']['cost'],
                'billing' => [
                    'countryCodeAlpha2' => Address::getCountryIsoCode2($partner_billing_address['country_id']),
                    'extendedAddress' => $partner_billing_address['street_address_2'],
                    'firstName' => $partner_billing_address['firstname'],
                    'lastName' => $partner_billing_address['lastname'],
                    'locality' => $partner_billing_address['city'],
                    'postalCode' => $partner_billing_address['postcode'],
                    'region' => ((int)$partner_billing_address['zone_id'] > 0) ? Address::getZoneCode($partner_billing_address['zone_id']) : $partner_billing_address['state'],
                    'streetAddress' => $partner_billing_address['street_address']
                ],
                'customer' => [
                    'company' => $partner['title']
                ],
                'customFields' => [
                    'user_id' => $_SESSION[OSCOM::getSite()]['Account']['id']
                ]
            ];

            if (isset($totals['tax'])) {
                $data['taxAmount'] = $totals['tax'][array_key_first($totals['tax'])]['cost'];
            }

            $braintree_result = Braintree::doSale($data, [
                'user_group' => 'partner',
                'module' => 'partnership',
                'action' => 'extension'
            ], [
                'user_id' => $_SESSION[OSCOM::getSite()]['Account']['id'],
                'title' => OSCOM::getDef('cs_purchase_title'),
                'billing_address' => [
                    'gender' => null,
                    'company' => $partner_billing_address['company'],
                    'firstname' => $partner_billing_address['firstname'],
                    'lastname' => $partner_billing_address['lastname'],
                    'street' => $partner_billing_address['street_address'],
                    'street2' => $partner_billing_address['street_address_2'],
                    'suburb' => $partner_billing_address['suburb'],
                    'zip' => $partner_billing_address['postcode'],
                    'city' => $partner_billing_address['city'],
                    'state' => $partner_billing_address['state'],
                    'telephone' => $partner_billing_address['telephone'],
                    'fax' => $partner_billing_address['fax'],
                    'other' => $partner_billing_address['other_info'],
                    'country_iso_2' => Address::getCountryIsoCode2($partner_billing_address['country_id']),
                    'zone_code' => ((int)$partner_billing_address['zone_id'] > 0) ? Address::getZoneCode($partner_billing_address['zone_id']) : null,
                    'vat_id' => $partner_campaign['billing_vat_id']
                ],
                'items' => $items,
                'totals' => $totals,
                'cost' => $totals['total']['cost'],
                'currency_id' => $OSCOM_Currency->get('id'),
                'language_id' => $OSCOM_Language->getID(),
                'status' => Invoices::STATUS_NEW
            ]);

            if ($braintree_result->success !== true) {
                throw new BraintreeException();
            }

            $result['rpcStatus'] = RPC::STATUS_SUCCESS;

            $data = [
                'partner_id' => $partner['id'],
                'package_id' => Partner::getPackageId($plan),
                'date_added' => 'now()',
                'date_start' => $date_start->format('Y-m-d H:i:s'),
                'date_end' => $date_end->format('Y-m-d H:i:s'),
                'cost' => $totals['total']['cost'],
                'braintree_transaction_id' => $braintree_result->transaction->id
            ];

            $OSCOM_PDO->save('website_partner_transaction', $data);

            Partner::updatePackageLevelStatus($level_id);

            Cache::clear('website_partner-' . $partner['code']);
            Cache::clear('website_partner_promotions');
            Cache::clear('website_partners');
            Cache::clear('carousel-website-frontpage');

            $email_txt_file = $OSCOM_Template->getPageContentsFile('email_partner_extension.txt');
            $email_txt_tmpl = file_exists($email_txt_file) ? file_get_contents($email_txt_file) : null;

            $email_html_file = $OSCOM_Template->getPageContentsFile('email_partner_extension.html');
            $email_html_tmpl = file_exists($email_html_file) ? file_get_contents($email_html_file) : null;

            $OSCOM_Template->setValue('user_name', $_SESSION[OSCOM::getSite()]['Account']['name']);
            $OSCOM_Template->setValue('partnership_extension_plan', $packages[$plan]['title']);
            $OSCOM_Template->setValue('partnership_extension_period', $date_start->format('jS M, Y') . ' - ' . $date_end->format('jS M, Y'));

            foreach (Partner::getCampaignAdmins($partner['code']) as $admin_id) {
                $admin = Users::get($admin_id);
                $OSCOM_Template->setValue('partner_admin_name', $admin['name'], true);

                $email_txt = null;
                $email_html = null;

                if (isset($email_txt_tmpl)) {
                    $email_txt = $OSCOM_Template->parseContent($email_txt_tmpl);
                }

                if (isset($email_html_tmpl)) {
                    $email_html = $OSCOM_Template->parseContent($email_html_tmpl);
                }

                if (!empty($email_txt) || !empty($email_html)) {
                    $OSCOM_Mail = new Mail($admin['email'], $admin['name'], 'sales@oscommerce.com', 'osCommerce', OSCOM::getDef('email_partner_extension_subject'));

                    if (!empty($email_txt)) {
                        $OSCOM_Mail->setBodyPlain($email_txt);
                    }

                    if (!empty($email_html)) {
                        $OSCOM_Mail->setBodyHTML($email_html);
                    }

                    $OSCOM_Mail->send();
                }
            }
        } catch (RPCException $e) {
            $code = $e->getCode();

            if (isset($code)) {
                $result['rpcStatus'] = $code;
            }
        } catch (BraintreeException $e) {
            $message = OSCOM::getDef('error_partner_payment_general');

            if (isset($braintree_result->transaction)) {
                if (isset($braintree_result->transaction->gatewayRejectionReason)) {
                    switch ($braintree_result->transaction->gatewayRejectionReason) {
                        case 'cvv':
                            $message = OSCOM::getDef('error_partner_payment_cvv');
                            break;

                        case 'avs':
                            $message = OSCOM::getDef('error_partner_payment_avs');
                            break;

                        case 'avs_and_cvv':
                            $message = OSCOM::getDef('error_partner_payment_cvv_avs');
                            break;
                    }
                }
            }

            $result['errorMessage'] = $message;
        } catch (\Exception $e) {
            trigger_error($e->getMessage());
        }

        if (!isset($result['rpcStatus'])) {
            $result['rpcStatus'] = RPC::STATUS_ERROR;
        }

        echo json_encode($result);
    }
}
