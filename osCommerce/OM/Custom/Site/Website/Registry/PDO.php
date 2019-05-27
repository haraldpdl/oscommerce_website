<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website\Registry;

use osCommerce\OM\Core\{
    OSCOM,
    PDO as OSCOM_PDO
};

class PDO extends \osCommerce\OM\Core\RegistryAbstract
{
    public function __construct()
    {
        $driver_options = [];

        if (OSCOM::getConfig('db_server_persistent_connections', 'Website') === 'true') {
            $driver_options[\PDO::ATTR_PERSISTENT] = true;
        }

        $this->value = OSCOM_PDO::initialize(OSCOM::getConfig('db_server', 'Website'), OSCOM::getConfig('db_server_username', 'Website'), OSCOM::getConfig('db_server_password', 'Website'), OSCOM::getConfig('db_database', 'Website'), (int)OSCOM::getConfig('db_server_port', 'Website'), OSCOM::getConfig('db_driver', 'Website'), $driver_options);
        $this->value->setTablePrefix(OSCOM::getConfig('db_table_prefix', 'Website'));
    }
}
