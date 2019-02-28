<?php
/**
 * osCommerce Website
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

class ContactInquiry
{
    const STATUS_PENDING = 1;
    const STATUS_NOTIFIED = 2;
    const DEPARTMENT_MODULES = [
        'General',
        'Partners'
    ];

    public static function canSend(string $department = null): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress())),
            'date' => (new \DateTime('5 minutes ago'))->format('Y-m-d H:i:s')
        ];

        if (isset($department)) {
            if (!static::departmentExists($department)) {
                trigger_error('ContactInquiry::canSend(): Unknown department: ' . $department);

                return false;
            }

            $data['department'] = $department;
        }

        $result = $OSCOM_PDO->call('CheckSendAccess', $data);

        if (!is_bool($result)) {
            $result = false;
        }

        return $result;
    }

    public static function save(array $params): ?string
    {
        $OSCOM_Language = Registry::get('Language');
        $OSCOM_PDO = Registry::get('PDO');
        $OSCOM_Session = Registry::get('Session');

        if (!isset($params['department']) || !static::departmentExists($params['department'])) {
            trigger_error('ContactInquiry::save(): Unknown department: ' . ($params['department'] ?? 'null'));

            return null;
        }

        $user_id = null;

        if ($OSCOM_Session->hasStarted() && isset($_SESSION[OSCOM::getSite()]['Account'])) {
            $user_id = $_SESSION[OSCOM::getSite()]['Account']['id'];
        }

        $inquiry_id = static::generateId();

        $data = [
            'inquiry_id' => $inquiry_id,
            'company' => $params['company'],
            'name' => $params['name'],
            'email' => $params['email'],
            'inquiry' => $params['inquiry'],
            'department' => $params['department'],
            'status' => static::STATUS_PENDING,
            'user_id' => $user_id,
            'ip_address' => sprintf('%u', ip2long(OSCOM::getIPAddress())),
            'language_id' => $OSCOM_Language->getID()
        ];

        if ($OSCOM_PDO->call('Save', $data)) {
            return $inquiry_id;
        }

        return null;
    }

    public static function generateId(): string
    {
        $OSCOM_PDO = Registry::get('PDO');

        while (true) {
            $id = Hash::getRandomString(8);

            if ($OSCOM_PDO->call('Exists', ['id' => $id]) === false) {
                return $id;
            }
        }
    }

    public static function getPending(string $department = null): array
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'status' => static::STATUS_PENDING
        ];

        if (isset($department)) {
            if (!static::departmentExists($department)) {
                trigger_error('ContactInquiry::getPending(): Unknown department: ' . $department);

                return [];
            }

            $data['department'] = $department;
        }

        return $OSCOM_PDO->call('GetWithStatus', $data);
    }

    public static function setStatus(int $id, int $status): bool
    {
        $OSCOM_PDO = Registry::get('PDO');

        $data = [
            'id' => $id,
            'status' => $status
        ];

        return $OSCOM_PDO->call('SetStatus', $data);
    }

    public static function departmentExists(string $department): bool
    {
        return in_array($department, static::DEPARTMENT_MODULES);
    }
}
