<?php
/**
 * osCommerce Website
 * 
 * @copyright Copyright (c) 2013 osCommerce; http://www.oscommerce.com
 * @license BSD License; http://www.oscommerce.com/bsdlicense.txt
 */

  namespace osCommerce\OM\Core\Site\Website;

  use osCommerce\OM\Core\OSCOM;

  class Invision {
    protected $_login_key;
    protected $_login_password;
    protected $_user_data;
    protected $_user_ip_address;
    protected $_login_success = false;
    protected $_has_access = false;
    protected $_has_no_access_reason = '';

    public function __construct($key = null, $password = null) {
      $this->_login_key = $key;
      $this->_login_password = !empty($password) ? md5($password) : null;

      $this->setIPAddress();
    }

    public function autoLogin($key, $hash) {
      if ( !empty($key) && !empty($hash) ) {
        $request = xmlrpc_encode_request('canAutoLogin', array('api_key' => OSCOM::getConfig('community_api_key'),
                                                               'api_module' => 'oscommerce',
                                                               'member_id' => $key,
                                                               'pass_hash' => $hash));

        $response = xmlrpc_decode($this->getResponse($request));

        if ( is_array($response) && !empty($response) && isset($response['result']) && ($response['result'] === true) && isset($response['member'][0]['member_id']) && ($response['member'][0]['member_id'] > 0) && ($response['member'][0]['member_id'] == $key) ) {
          $user_data = array('id' => (int)$response['member'][0]['member_id'],
                             'group_id' => (int)$response['member'][0]['member_group_id'],
                             'email' => $response['member'][0]['email'],
                             'name' => $response['member'][0]['members_display_name'],
                             'restrict_post' => $response['member'][0]['restrict_post'],
                             'mod_posts' => $response['member'][0]['mod_posts'],
                             'temp_ban' => $response['member'][0]['temp_ban']);

          $this->_user_data = $user_data;

          $this->_login_success = true;
        }
      }

      return $this->_login_success;
    }

    public function perform() {
      if ( empty($this->_login_key) || empty($this->_login_password) ) {
        $this->_has_no_access_reason = 'All fields are required.';
      } else {
        $request = xmlrpc_encode_request('verifyMember', array('api_key' => OSCOM::getConfig('community_api_key'),
                                                               'api_module' => 'oscommerce',
                                                               'username' => $this->_login_key,
                                                               'password' => $this->_login_password));

        $response = xmlrpc_decode($this->getResponse($request));

        if ( is_array($response) && !empty($response) && isset($response['result']) && ($response['result'] === true) && isset($response['member'][0]['member_id']) && ($response['member'][0]['member_id'] > 0) ) {
          $user_data = array('id' => (int)$response['member'][0]['member_id'],
                             'group_id' => (int)$response['member'][0]['member_group_id'],
                             'email' => $response['member'][0]['email'],
                             'name' => $response['member'][0]['members_display_name'],
                             'restrict_post' => $response['member'][0]['restrict_post'],
                             'mod_posts' => $response['member'][0]['mod_posts'],
                             'temp_ban' => $response['member'][0]['temp_ban']);

          $this->_user_data = $user_data;

          $this->_login_success = true;
        } else {
          $this->_has_no_access_reason = 'Username or password are incorrect.';
        }
      }

      return $this->_login_success;
    }

    public function hasAccess() {
      if ( ($this->_login_success === true) && !empty($this->_user_data) ) {
        if ( in_array($this->_user_data['group_id'], array(1, 2, 5)) ) {
          $this->_has_no_access_reason = 'User account not validated or is currently suspended.';
        } elseif ( !empty($this->_user_data['restrict_post']) && ($this->_user_data['restrict_post'] != '0') ) {
          $this->_has_no_access_reason = 'User account currently has restricted posting abilities.';
        } elseif ( !empty($this->_user_data['mod_posts']) && ($this->_user_data['mod_posts'] != '0') ) {
          $this->_has_no_access_reason = 'User account currently has review posting abilities.';
        } elseif ( !empty($this->_user_data['temp_ban']) && ($this->_user_data['temp_ban'] != '0') ) {
          $this->_has_no_access_reason = 'User account is temporarily suspended.';
        } else {
          $this->_has_access = true;
        }
      }

      return $this->_has_access;
    }

    public function logOut() {
      setcookie('member_id', '-1', time() - 60*60*24*365, '/', '.oscommerce.com');
      setcookie('pass_hash', '-1', time() - 60*60*24*365, '/', '.oscommerce.com');

      $this->_has_access = false;
      $this->_has_no_access_reason = 'User has logged out.';
      $this->_login_success = false;
      $this->_user_data = null;
    }

    protected function setIPAddress() {
      $ip_address = false;
      $addrs = array();

      if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
        foreach ( array_reverse(explode(',' , $_SERVER['HTTP_X_FORWARDED_FOR'])) as $x_f ) {
          $x_f = trim($x_f);

          if ( preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $x_f) ) {
            $addrs[] = $x_f;
          }
        }
      }

      $addrs[] = $_SERVER['REMOTE_ADDR'];

      if ( isset($_SERVER['HTTP_PROXY_USER']) ) {
        $addrs[] = $_SERVER['HTTP_PROXY_USER'];
      }

      if ( isset($_SERVER['HTTP_CLIENT_IP']) ) {
        $addrs[] = $_SERVER['HTTP_CLIENT_IP'];
      }

      foreach ( $addrs as $ip ) {
        if ( !empty($ip) ) {
          $ip_address = preg_replace("/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", "\\1.\\2.\\3.\\4", $ip);

          break;
        }
      }

      $this->_user_ip_address = $ip_address;
    }

    public function getUserData($key = null) {
      if ( isset($key) ) {
        return $this->_user_data[$key];
      }

      return $this->_user_data;
    }

    public function getNoAccessReason() {
      return $this->_has_no_access_reason;
    }

    public function getIPAddress() {
      return $this->_user_ip_address;
    }

    protected function getResponse($request) {
      $parameters = array('server' => parse_url(OSCOM::getConfig('community_api_address')),
                          'parameters' => $request);

      if ( !isset($parameters['server']['port']) ) {
        $parameters['server']['port'] = ($parameters['server']['scheme'] == 'https') ? 443 : 80;
      }

      if ( !isset($parameters['server']['path']) ) {
        $parameters['server']['path'] = '/';
      }

      $curl = curl_init($parameters['server']['scheme'] . '://' . $parameters['server']['host'] . $parameters['server']['path'] . (isset($parameters['server']['query']) ? '?' . $parameters['server']['query'] : ''));

      $curl_options = array(CURLOPT_PORT => $parameters['server']['port'],
                            CURLOPT_HEADER => false,
                            CURLOPT_HTTPHEADER, array('Content-Type: application/xml; charset=utf-8'),
                            CURLOPT_SSL_VERIFYPEER => true,
                            CURLOPT_SSL_VERIFYHOST => 2,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_FORBID_REUSE => true,
                            CURLOPT_FRESH_CONNECT => true,
                            CURLOPT_FOLLOWLOCATION => false,
                            CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => $parameters['parameters']);

      curl_setopt_array($curl, $curl_options);
      $result = curl_exec($curl);

      if ( $result === false ) {
        curl_close($curl);

        return false;
      }

      curl_close($curl);

      return $result;
    }
  }
?>
