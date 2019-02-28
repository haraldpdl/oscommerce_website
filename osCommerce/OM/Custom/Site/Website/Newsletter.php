<?php
/**
 * osCommerce Online Merchant
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
    Hash,
    OSCOM,
    Registry
};

class Newsletter
{
    public static function isSubscribed(string $email, int $list_id): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'email' => $email,
            'list_id' => $list_id
        ];

        return $OSCOM_PDO->call('IsSubscribed', $data);
    }

    public static function isSubscriptionPending(string $email, int $list_id): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'email' => $email,
            'list_id' => $list_id
        ];

        return $OSCOM_PDO->call('IsSubscriptionPending', $data);
    }

    public static function savePendingSubscription(string $email, int $list_id): ?string
    {
        $OSCOM_PDO = Registry::get('PDO');

        $pending_key = Hash::getRandomString(32);

        $data = [
            'key' => $pending_key,
            'email' => $email,
            'list_id' => $list_id,
            'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress()))
        ];

        if ($OSCOM_PDO->call('SavePendingSubscription', $data)) {
            return $pending_key;
        }

        return null;
    }

    public static function getPendingSubscriptionKey(string $email, int $list_id): ?array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'email' => $email,
            'list_id' => $list_id
        ];

        $result = $OSCOM_PDO->call('GetPendingSubscriptionKey', $data);

        if (is_array($result)) {
            return $result;
        }

        return null;
    }

    public static function getPendingSubscription(string $pending_key): ?array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'key' => $pending_key
        ];

        $result = $OSCOM_PDO->call('GetPendingSubscription', $data);

        if (is_array($result)) {
            return $result;
        }

        return null;
    }

    public static function updatePendingSubscription(string $email, int $list_id): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'email' => $email,
            'list_id' => $list_id,
            'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress()))
        ];

        return $OSCOM_PDO->call('UpdatePendingSubscription', $data);
    }

    public static function subscribe(string $pending_key): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $pending = static::getPendingSubscription($pending_key);

        if ($pending !== null) {
            try {
                $OSCOM_PDO->beginTransaction();

                $data = [
                    'key' => Hash::getRandomString(32),
                    'list_id' => $pending['list_id'],
                    'email' => $pending['email'],
                    'name' => $pending['name'],
                    'optin_time' => $pending['optin_time'],
                    'optin_ip' => $pending['optin_ip'],
                    'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress()))
                ];

                $OSCOM_PDO->call('SaveSubscription', $data);

                $data = [
                    'email' => $pending['email'],
                    'list_id' => $pending['list_id']
                ];

                $OSCOM_PDO->call('DeletePendingSubscription', $data);

                $OSCOM_PDO->commit();

                return true;
            } catch (\Exception $e) {
                $OSCOM_PDO->rollBack();

                trigger_error($e->getMessage());
            }
        }

        return false;
    }

    public static function getSubscriptionKey(string $email, int $list_id): ?array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'email' => $email,
            'list_id' => $list_id
        ];

        $result = $OSCOM_PDO->call('GetSubscriptionKey', $data);

        if (is_array($result)) {
            return $result;
        }

        return null;
    }

    public static function getSubscription(string $subscription_key): ?array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'key' => $subscription_key
        ];

        $result = $OSCOM_PDO->call('GetSubscription', $data);

        if (is_array($result)) {
            return $result;
        }

        return null;
    }

    public static function updateSubscriptionOptOutRequest(string $email, int $list_id): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'email' => $email,
            'list_id' => $list_id,
            'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress()))
        ];

        return $OSCOM_PDO->call('UpdateSubscriptionOptOutRequest', $data);
    }

    public static function unsubscribe(string $subscription_key): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $sub = static::getSubscription($subscription_key);

        if ($sub !== null) {
            try {
                $OSCOM_PDO->beginTransaction();

                $data = [
                    'key' => $sub['sub_key'],
                    'list_id' => $sub['list_id'],
                    'email' => $sub['email'],
                    'name' => $sub['name'],
                    'optin_time' => $sub['optin_time'],
                    'optin_ip' => $sub['optin_ip'],
                    'confirm_time' => $sub['confirm_time'],
                    'confirm_ip' => $sub['confirm_ip'],
                    'optout_req_time' => $sub['optout_req_time'],
                    'optout_req_ip' => $sub['optout_req_ip'],
                    'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress()))
                ];

                $OSCOM_PDO->call('SaveUnsubscription', $data);

                $data = [
                    'email' => $sub['email'],
                    'list_id' => $sub['list_id']
                ];

                $OSCOM_PDO->call('DeleteSubscription', $data);

                $OSCOM_PDO->commit();

                return true;
            } catch (\Exception $e) {
                $OSCOM_PDO->rollBack();

                trigger_error($e->getMessage());
            }
        }

        return false;
    }
}
