<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Scripts\CurrencyExchange;

use osCommerce\OM\Core\{
    Cache,
    HttpRequest,
    OSCOM,
    Registry,
    RunScript
};

class UpdateRates implements \osCommerce\OM\Core\RunScriptInterface
{
    public static function execute()
    {
        OSCOM::initialize('Website');

        try {
            $XML = HttpRequest::getResponse([
                'url' => 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml'
            ]);

            if (empty($XML)) {
                throw new \Exception('Can not load currency rates from the European Central Bank website');
            }

            $OSCOM_Currency = Registry::get('Currency');
            $OSCOM_PDO = Registry::get('PDO');

            $currencies = [];

            foreach ($OSCOM_Currency->getAll() as $c) {
                $currencies[$c['code']] = null;
            }

            $XML = new \SimpleXMLElement($XML);

            foreach ($XML->Cube->Cube->Cube as $rate) {
                if (array_key_exists((string)$rate['currency'], $currencies)) {
                    $currencies[(string)$rate['currency']] = (float)$rate['rate'];
                }
            }

            foreach ($currencies as $code => $value) {
                if (!is_null($value)) {
                    try {
                        $OSCOM_PDO->beginTransaction();

                        $OSCOM_PDO->save('currencies', [
                            'value' => $value,
                            'last_updated' => 'now()'
                        ], [
                            'code' => $code
                        ]);

                        $OSCOM_PDO->save('currencies_history', [
                            'currencies_id' => $OSCOM_Currency->get('id', $code),
                            'value' => $value,
                            'date_added' => 'now()'
                        ]);

                        $OSCOM_PDO->commit();
                    } catch (\PDOException $e) {
                        $OSCOM_PDO->rollBack();

                        trigger_error($e->getMessage());
                    }
                }
            }

            Cache::clear('currencies');
        } catch (\Exception $e) {
            RunScript::error('(CurrencyExchange\UpdateRates) ' . $e->getMessage());
        }
    }
}
