<?php
/**
 * osCommerce Website
 *
 * @copyright (c) 2019 osCommerce; https://www.oscommerce.com
 * @license MIT; https://www.oscommerce.com/license/mit.txt
 */

namespace osCommerce\OM\Core\Site\Website;

use osCommerce\OM\Core\{
    Is,
    OSCOM
};

require_once(OSCOM::getConfig('forum_dir_path', 'Website') . 'init_extern.php');

class Invision
{
    const COOKIE_DEVICE_KEY = 'ips4_device_key';
    const COOKIE_LOGIN_KEY = 'ips4_login_key';
    const COOKIE_MEMBER_ID = 'ips4_member_id';
    const COOKIE_SESSION_NAME = 'ips4_IPSSessionFront';

    const DEFAULT_TOTAL_ONLINE_USERS = 700;
    const DEFAULT_TOTAL_POSTINGS = 1600000;
    const DEFAULT_TOTAL_USERS = 300000;

    const FORUM_ADDONS_CATEGORY_IDS = [
        51
    ];

    const MAX_PROFILE_PHOTO_DIMENSION = 600;
    const MAX_PROFILE_PHOTO_FILESIZE = 307200;

    const CUSTOM_FIELDS = [
        'full_name' => [ 'group_id' => 2, 'id' => 1 ],
        'website' => [ 'group_id' => 2, 'id' => 12 ],
        'gender' => [ 'group_id' => 2, 'id' => 14 ],
        'location' => [ 'group_id' => 2, 'id' => 15 ],
        'twitter' => [ 'group_id' => 2, 'id' => 24 ],
        'bio_short' => [ 'group_id' => 2, 'id' => 25 ],
        'company' => [ 'group_id' => 2, 'id' => 26 ],
        'amb_level' => [ 'group_id' => 3, 'id' => 23 ]
    ];

    const CLUB_AMBASSADORS_ID = 7;

    public static function fetchMember($search, $key, bool $return_raw = false)
    {
        if (empty($search)) {
            return false;
        }

        if (!in_array($key, ['id', 'email', 'username'])) {
            return false;
        }

        if (($key == 'id') && !Is::Integer($search)) {
            return false;
        }

        if (($key == 'email') && !Is::EmailAddress($search)) {
            return false;
        }

        $load_member_key = null;

        if ($key === 'username') {
            $load_member_key = 'name';
        } elseif ($key === 'email') {
            $load_member_key = 'email';
        }

        $member = \IPS\Member::load($search, $load_member_key);

        if ($member->member_id) {
            if ($return_raw === true) {
                return $member;
            }

            return static::getUserDataArray($member);
        }

        return false;
    }

    public static function checkMemberExists($search, $key): bool
    {
        return is_array(static::fetchMember($search, $key));
    }

    public static function createUser($username, $email, $password)
    {
        if (empty($username) || empty($email) || !Is::EmailAddress($email) || empty($password)) {
            return false;
        }

        $result = false;

        try {
            $member = new \IPS\Member();

            $member->name = $username;
            $member->email = $email;
            $member->setLocalPassword($password);
            $member->member_group_id = Users::GROUP_MEMBER_ID;
            $member->members_bitoptions['view_sigs'] = true;
            $member->members_bitoptions['validating'] = true;
            $member->last_visit = time();

            $member->save();

            $member->invalidateSessionsAndLogins();

            // Prevent duplicates from double clicking, etc
            \IPS\Db::i()->delete('core_validating', ['member_id=? and new_reg=1', $member->member_id]);

            $vid = md5($member->members_pass_hash . \IPS\Login::generateRandomString());

            \IPS\Db::i()->insert('core_validating', [
                'vid' => $vid,
                'member_id' => $member->member_id,
                'entry_date' => time(),
                'new_reg' => 1,
                'ip_address' => $member->ip_address,
                'spam_flag' => false,
                'user_verified' => false,
                'email_sent' => time(),
                'do_not_delete' => false
            ]);

            $result = static::getUserDataArray($member);
        } catch (\Exception $e) {
            trigger_error($e->getMessage());
        }

        return $result;
    }

    public static function verifyUserKey(int $user_id, $key)
    {
        if (!Is::Integer($user_id, 1)) {
            return false;
        }

        if (strlen($key) !== 32) {
            return false;
        }

        $user = static::fetchMember($user_id, 'id');

        if (($user !== false) && is_array($user) && isset($user['id']) && ($user['id'] > 0)) {
            if (($user['verified'] === false) && !empty($user['val_newreg_id'])) {
                if ($user['val_newreg_id'] == $key) {
                    $member = \IPS\Member::load($user_id);

                    \IPS\Db::i()->delete('core_validating', ['member_id=?', $member->member_id]);

                    /* Reset the flag */
                    $member->members_bitoptions['validating'] = false;
                    $member->save();

                    /* Sync */
                    $member->memberSync('onValidate');

                    return true;
                } else {
                    return [
                        'error' => 'invalid_key'
                    ];
                }
            } else {
                return [
                    'error' => 'already_verified'
                ];
            }
        } else {
            return [
                'error' => 'invalid_member'
            ];
        }

        return false;
    }

    public static function getPasswordResetKey($user_id, bool $generate_new = false): array
    {
        $result = [];

        if (!Is::Integer($user_id, 1)) {
            $result['error'] = 'invalid_member';

            return $result;
        }

        $member = \IPS\Member::load($user_id);

        if (isset($member->member_id) && ($member->member_id > 0)) {
            $send_email = true;

            try {
                $existing = \IPS\Db::i()->select(['vid', 'email_sent'], 'core_validating', ['member_id=? AND lost_pass=1', $member->member_id])->first();

                $vid = $existing['vid'];

                /* If we sent a lost password email within the last 15 minutes, don't send another one otherwise someone could be a nuisence */
                if ($existing['email_sent'] && ($existing['email_sent'] > (time() - 900))) {
                    $send_email = false;
                } else {
                    \IPS\Db::i()->update('core_validating', ['email_sent' => time()], ['vid=?', $vid]);
                }
            } catch (\UnderflowException $e) {
                if ($generate_new === true) {
                    $vid = md5($member->members_pass_hash . \IPS\Login::generateRandomString());

                    \IPS\Db::i()->insert('core_validating', [
                        'vid' => $vid,
                        'member_id' => $member->member_id,
                        'entry_date' => time(),
                        'lost_pass' => 1,
                        'ip_address' => $member->ip_address,
                        'email_sent' => time()
                    ]);
                } else {
                    $result['error'] = 'not_found';
                }
            }

            if (isset($vid)) {
                $result['key'] = $vid;
                $result['id'] = $member->member_id;
                $result['name'] = $member->name;
                $result['email'] = $member->email;
                $result['send_email'] = $send_email;
            }
        } else {
            $result['error'] = 'invalid_member';
        }

        return $result;
    }

    public static function deletePasswordResetKey($user_id): bool
    {
        if (Is::Integer($user_id, 1)) {
            $member = \IPS\Member::load($user_id);

            if (isset($member->member_id) && ($member->member_id > 0)) {
                /* Reset the failed logins storage - we don't need to save because the login handler will do that for us later */
                $member->failed_logins = [];

                $member->save();

                $member->invalidateSessionsAndLogins();

                \IPS\Member\Device::loadOrCreate($member)->updateAfterAuthentication(isset($_COOKIE[static::COOKIE_LOGIN_KEY]));

                /* Delete validating record and log in */
                \IPS\Db::i()->delete('core_validating', ['member_id=? AND lost_pass=1', $member->member_id]);

                return true;
            }
        }

        return false;
    }

    public static function isFilterBanned(string $source, string $key = 'email'): bool
    {
        if (!in_array($key, ['email', 'name', 'ip'])) {
            trigger_error('OSCOM\Invision::isFilterBanned() unknown key: ' . $key);

            return true;
        }

        foreach (iterator_to_array(\IPS\Db::i()->select('ban_content', 'core_banfilters', ['ban_type=?', $key])) as $filter) {
            if (preg_match('/^' . str_replace('\*', '.*', preg_quote($filter, '/')) . '$/i', $source)) {
                trigger_error('OSCOM\Invision::isFilterBanned(): ' . $source . ' (' . $key . ')');

                return true;
            }
        }

        return false;
    }

    public static function saveUser(int $id, array $data)
    {
        $member = \IPS\Member::load($id);

        if (isset($member->member_id) && ($member->member_id > 0)) {
            $old_email = null;

            if (isset($data['name'])) {
                $member->name = $data['name'];
            }

            if (isset($data['email'])) {
                $old_email = $member->email;

                $member->email = $data['email'];
            }

            if (isset($data['password'])) {
                $member->setLocalPassword($data['password']);
            }

            if (isset($data['group'])) {
                $member->member_group_id = $data['group'];
            }

            if (isset($data['profilePhoto'])) {
                try {
                    $photo = file_get_contents($data['profilePhoto']);

                    if ($photo === false) {
                        throw new \Exception('no_profile_photo_file');
                    }

                    $image = \IPS\Image::create($photo);

                    if ($image->isAnimatedGif) {
                        throw new \Exception('member_photo_upload_no_animated');
                    }

                    if (($image->width > static::MAX_PROFILE_PHOTO_DIMENSION) || ($image->height > static::MAX_PROFILE_PHOTO_DIMENSION)) {
                        $image->resizeToMax(static::MAX_PROFILE_PHOTO_DIMENSION, static::MAX_PROFILE_PHOTO_DIMENSION);
                    }

                    if (strlen($image) > static::MAX_PROFILE_PHOTO_FILESIZE) {
                        throw new \Exception('upload_too_big_unspecific');
                    }

                    $newFile = \IPS\File::create('core_Profile', 'imported-photo-' . $member->member_id . '.' . $image->type, (string)$image);
                    $thumbnail = $newFile->thumbnail('core_Profile', \IPS\PHOTO_THUMBNAIL_SIZE, \IPS\PHOTO_THUMBNAIL_SIZE, true);

                    $member->pp_photo_type = 'custom';
                    $member->pp_main_photo = (string)$newFile;
                    $member->pp_thumb_photo = (string)$thumbnail;
                    $member->photo_last_update = time();
                } catch (\Exception $e) {
                    trigger_error($e->getMessage());
                }
            }

            if (isset($data['birthday'])) {
                $bday = explode('/', $data['birthday'], 3);

                $member->bday_month = $bday[0] ? str_pad($bday[0], 2, '0', \STR_PAD_LEFT) : null;
                $member->bday_day = $bday[1] ? str_pad($bday[1], 2, '0', \STR_PAD_LEFT) : null;
                $member->bday_year = $bday[2] ?? null;
            }

            if (isset($data['customFields'])) {
                try {
                    $profileFields = \IPS\Db::i()->select('*', 'core_pfields_content', ['member_id=?', $member->member_id])->first();
                } catch (\UnderflowException $e) {
                    $profileFields = [];
                }

                /* If \IPS\Db::i()->select()->first() has only one column, then the contents of that column is returned. We do not want this here. */
                if (!is_array($profileFields)) {
                    $profileFields = [];
                }

                $profileFields['member_id'] = $member->member_id;

                foreach ($data['customFields'] as $k => $v) {
                    $profileFields['field_' . $k] = $v;
                }

                \IPS\Db::i()->replace('core_pfields_content', $profileFields);

                $member->changedCustomFields = $profileFields;
            }

            $member->save();

            if (isset($data['email'])) {
                $member->memberSync('onEmailChange', [$data['email'], $old_email]);
            }

            if (isset($data['password'])) {
                $member->memberSync('onPassChange', [$data['password']]);
            }

            if (isset($data['clubs']) && is_array($data['clubs'])) {
                foreach ($data['clubs'] as $c) {
                    if (Is::Integer($c) && !in_array($c, $member->clubs())) {
                        $club = \IPS\Member\Club::load((int)$c);
                        $club->addMember($member, \IPS\Member\Club::STATUS_MEMBER, true, null, null, true);
                        $club->recountMembers();
                    }
                }
            }

            return static::getUserDataArray($member);
        }

        return false;
    }

    public static function canLogin(string $username, string $password)
    {
        $login = new \IPS\Login(\IPS\Http\Url::external(OSCOM::getLink('Website', 'Account', 'Login', 'SSL')));

        $success = null;
        $fails = [];

        foreach ($login->usernamePasswordMethods() as $method) {
            try {
                $member = $method->authenticateUsernamePassword($login, $username, $password);

                if ($member->member_id) {
                    \IPS\Login::checkIfAccountIsLocked($member, true);
                    $success = new \IPS\Login\Success($member, $method, isset(\IPS\Request::i()->remember_me));
                    break;
                }
            } catch (\IPS\Login\Exception $e) {
                if ($e->getCode() === \IPS\Login\Exception::BAD_PASSWORD and $e->member) {
                    $fails[$e->member->member_id] = $e->member;
                }
            }
        }

        foreach ($fails as $failedMember) {
            if (!$success or $success->member->member_id != $failedMember->member_id) {
                $failedLogins = is_array($failedMember->failed_logins) ? $failedMember->failed_logins : [];
                $failedLogins[\IPS\Request::i()->ipAddress()][] = time();
                $failedMember->failed_logins = $failedLogins;
                $failedMember->save();
            }
        }

        if ($success) {
            return static::getUserDataArray(\IPS\Member::load($success->member->member_id));
        }

        $failedMember = array_values($fails)[0];

        $unlockTime = $failedMember->unlockTime();

        if ($unlockTime === false) {
            $failedLogins = $failedMember->failed_logins;

            if (isset($failedLogins[\IPS\Request::i()->ipAddress()]) && (count($failedLogins[\IPS\Request::i()->ipAddress()]) > \IPS\Settings::i()->ipb_bruteforce_attempts)) {
                sort($failedLogins[\IPS\Request::i()->ipAddress()]);

                foreach ($failedLogins[\IPS\Request::i()->ipAddress()] as $k => $v) {
                    if ($v < \IPS\DateTime::create()->sub(new \DateInterval('PT' . \IPS\Settings::i()->ipb_bruteforce_period . 'M'))->getTimestamp()) {
                        unset($failedLogins[\IPS\Request::i()->ipAddress()][$k]);
                    } else {
                        break;
                    }
                }

                $failedMember->failed_logins = $failedLogins;
                $failedMember->save();

                $unlockTime = $failedMember->unlockTime();
            }
        }

        if ($unlockTime !== false) {
            if (count($failedMember->failed_logins[\IPS\Request::i()->ipAddress()]) == \IPS\Settings::i()->ipb_bruteforce_attempts) {
                $failedMember->logHistory('core', 'login', ['type' => 'lock', 'count' => count($failedMember->failed_logins[\IPS\Request::i()->ipAddress()]), 'unlockTime' => isset($unlockTime) ? $unlockTime->getTimestamp() : null]);

                trigger_error('Locked Login: ' . $username . ' (' . $failedMember->member_id . ')');
            }

            if (\IPS\Settings::i()->ipb_bruteforce_period and \IPS\Settings::i()->ipb_bruteforce_unlock) {
                return [
                    'locked' => $unlockTime->getTimestamp(),
                    'remaining' => $unlockTime->getTimestamp() - (new \IPS\DateTime())->getTimestamp()
                ];
            } else {
                return [
                    'locked' => 'permanent'
                ];
            }
        }

        return false;
    }

    public static function canAutoLogin()
    {
        $device = null;

        if (isset($_COOKIE[static::COOKIE_DEVICE_KEY]) && isset($_COOKIE[static::COOKIE_MEMBER_ID]) && isset($_COOKIE[static::COOKIE_LOGIN_KEY])) {
            /* Get the member we're trying to authenticate against - do not process cookie-based login if the account is locked */
            $member = \IPS\Member::load((int)$_COOKIE[static::COOKIE_MEMBER_ID]);

            if ($member->member_id && $member->unlockTime() === false) {
                /* Load and authenticate device device data */
                try {
                    /* Authenticate */
                    $device = \IPS\Member\Device::loadAndAuthenticate($_COOKIE[static::COOKIE_DEVICE_KEY], $member, $_COOKIE[static::COOKIE_LOGIN_KEY]);

                    /* Refresh the device key cookie */
                    $expire = new \DateTime();
                    $expire->add(new \DateInterval('P1Y'));

                    OSCOM::setCookie(static::COOKIE_DEVICE_KEY, $_COOKIE[static::COOKIE_DEVICE_KEY], $expire->getTimestamp(), null, null, true, true);

                    /* Update device */
                    $device->updateAfterAuthentication(true, null, false);

                    return static::getUserDataArray($member);
                } catch (\OutOfRangeException $e) {
                    /* If the device_key/login_key combination wasn't valid, this may be someone trying to bruteforce... */
                    /* ... so log it as a failed login */
                    $failedLogins = is_array($member->failed_logins) ? $member->failed_logins : [];
                    $failedLogins[OSCOM::getIPAddress()][] = time();

                    $member->failed_logins = $failedLogins;
                    $member->save();

                    static::killCookies();
                }
            } else {
                // If the member no longer exists, or the account is locked, set us as a guest and clear out those cookies
                static::killCookies();
            }
        }

        return false;
    }

    public static function findMembers(string $search): array
    {
        $result = [];

        if (empty($search)) {
            return $result;
        }

        try {
            $result = \IPS\Db::i()->select('member_id as id, name', 'core_members', ['name like ? and member_group_id not in (2, 5)', $search . '%'], 'name', 5);

            $result = iterator_to_array($result);
        } catch (\UnderflowException $e) {
        }

        if (!is_array($result)) {
            $result = [];
        }

        return $result;
    }

    public static function findMemberTopics(int $user_id, string $search, array $forum_filter = null): array
    {
        $result = [];

        if (empty($search)) {
            return $result;
        }

        $table_prefix = \IPS\Db::i()->prefix;

        $sql_query = 'select distinct t.tid as id, t.title, t.title_seo, t.forum_id, l.word_default as forum_title from ' . $table_prefix . 'forums_topics t, ' . $table_prefix . 'core_sys_lang_words l where t.starter_id = ? and t.title like ? and t.state = "open" ';

        if (!empty($forum_filter)) {
            $ids = [];

            foreach ($forum_filter as $filter) {
                if (Is::Integer($filter) && !in_array((int)$filter, $ids)) {
                    $ids[] = (int)$filter;
                }
            }

            if (!empty($ids)) {
                $filter_ids = implode(', ', $ids);

                $sql_query .= <<<EOD
and t.forum_id in (
  select id from {$table_prefix}forums_forums where parent_id in ({$filter_ids})
    UNION
      select id from {$table_prefix}forums_forums where parent_id in (select id from {$table_prefix}forums_forums where parent_id in ({$filter_ids}))
)
EOD;
            }
        }

        $sql_query .= ' and l.word_key = concat("forums_forum_", t.forum_id) order by title limit 5';

        try {
            $stmt = \IPS\Db::i()->preparedQuery($sql_query, [
                (int)$user_id,
                '%' . $search . '%'
            ]);

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result !== false) {
                $result = $result->fetch_all(\MYSQLI_ASSOC);
            }
        } catch (\UnderflowException $e) {
        }

        if (!is_array($result)) {
            $result = [];
        }

        return $result;
    }

    public static function getMemberTopic(int $user_id, int $topic_id, array $forum_filter = null): array
    {
        $table_prefix = \IPS\Db::i()->prefix;

        $result = [];

        $sql_query = 'select t.tid as id, t.title, t.title_seo, t.forum_id, l.word_default as forum_title from ' . $table_prefix . 'forums_topics t, ' . $table_prefix . 'core_sys_lang_words l where t.tid = ? and t.starter_id = ? and t.state = "open" ';

        if (!empty($forum_filter)) {
            $ids = [];

            foreach ($forum_filter as $filter) {
                if (Is::Integer($filter) && !in_array((int)$filter, $ids)) {
                    $ids[] = (int)$filter;
                }
            }

            if (!empty($ids)) {
                $filter_ids = implode(', ', $ids);

                $sql_query .= <<<EOD
and t.forum_id in (
  select id from {$table_prefix}forums_forums where parent_id in ({$filter_ids})
    UNION
      select id from {$table_prefix}forums_forums where parent_id in (select id from {$table_prefix}forums_forums where parent_id in ({$filter_ids}))
)
EOD;
            }
        }

        $sql_query .= ' and l.word_key = concat("forums_forum_", t.forum_id)';

        try {
            $stmt = \IPS\Db::i()->preparedQuery($sql_query, [
                (int)$topic_id,
                (int)$user_id
            ]);

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result !== false) {
                $result = $result->fetch_array(\MYSQLI_ASSOC);
            }
        } catch (\UnderflowException $e) {
        }

        if (!is_array($result)) {
            $result = [];
        }

        return $result;
    }

    public static function getMembersInGroup(int $group_id): array
    {
        $result = [];

        try {
            $result = \IPS\Db::i()->select('member_id as id', 'core_members', ['member_group_id in (?) or ? in (mgroup_others)', $group_id, $group_id], 'name');

            $result = iterator_to_array($result);
        } catch (\UnderflowException $e) {
        }

        if (!is_array($result)) {
            $result = [];
        }

        return $result;
    }

    public static function getUserCustomFields(int $user_id): array
    {
        $customFields = [];

        $fieldData = \IPS\core\ProfileFields\Field::fieldData();

        try {
            $fieldValues = \IPS\Db::i()->select('*', 'core_pfields_content', ['member_id=?', $user_id])->first();
        } catch (\UnderflowException $e) {
            $fieldValues = [];
        }

        /* If \IPS\Db::i()->select()->first() has only one column, then the contents of that column is returned. We do not want this here. */
        if (!is_array($fieldValues)) {
            $fieldValues = [];
        }

        foreach ($fieldData as $profileFieldGroup => $profileFields) {
            foreach ($profileFields as $field) {
                $customFields[$profileFieldGroup]['fields'][$field['pf_id']]['value'] = $fieldValues['field_' . $field['pf_id']] ?? null;
            }
        }

        return $customFields;
    }

    protected static function getUserDataArray(\IPS\Member $member): array
    {
        // $extra = $member->apiOutput();

        $group = \IPS\Member\Group::load($member->member_group_id);

        $extra = [
            'customFields' => static::getUserCustomFields($member->member_id)
        ];

        $val_newreg_id = null;

        try {
            $val_newreg_id = \IPS\Db::i()->select('*', 'core_validating', ['member_id=? AND new_reg=1', (int)$member->member_id])->first()['vid'];
        } catch (\UnderflowException $e) {
        }

        return [
            'id' => (int)$member->member_id,
            'name' => $member->name,
            'formatted_name' => $group->formatName($member->name),
            'full_name' => $extra['customFields'][static::CUSTOM_FIELDS['full_name']['group_id']]['fields'][static::CUSTOM_FIELDS['full_name']['id']]['value'],
            'title' => $member->member_title,
            'email' => $member->email,
            'group_id' => (int)$member->member_group_id,
            'is_ambassador' => (int)$member->member_group_id === Users::GROUP_AMBASSADOR_ID,
            'amb_level' => (int)$extra['customFields'][static::CUSTOM_FIELDS['amb_level']['group_id']]['fields'][static::CUSTOM_FIELDS['amb_level']['id']]['value'] ?? 0,
            'admin' => (int)$member->member_group_id === Users::GROUP_ADMIN_ID,
            'team' => in_array((int)$member->member_group_id, [Users::GROUP_TEAM_CORE_ID, Users::GROUP_TEAM_COMMUNITY_ID]),
            'verified' => (bool)$member->members_bitoptions['validating'] === false,
            'banned' => (int)$member->temp_ban !== 0,
            'restricted_post' => ((int)$member->restrict_post !== 0) || ((int)$member->mod_posts !== 0),
            'joined' => $member->joined->rfc3339(),
            'posts' => (int)$member->member_posts,
            'profile_url' => (string)$member->url(),
            'photo_url' => $member->get_photo(false),
            'val_newreg_id' => $val_newreg_id
        ];
    }

    public static function setCookies(array $member, bool $remember_me)
    {
        $member = \IPS\Member::load((int)$member['id']);

        \IPS\Member\Device::loadOrCreate($member)->updateAfterAuthentication($remember_me);
    }

    public static function killCookies()
    {
        $cookies = [
            static::COOKIE_LOGIN_KEY,
            static::COOKIE_MEMBER_ID,
            static::COOKIE_SESSION_NAME
        ];

        foreach ($cookies as $c) {
            if (isset($_COOKIE[$c])) {
                unset($_COOKIE[$c]);

                OSCOM::setCookie($c, '', time() - 31536000, null, null, true, true);
            }
        }
    }

    public static function getTotalUsers(): int
    {
        try {
            $result = \IPS\Db::i()->select('count(*)', 'core_members')->first();

            if (!Is::Integer($result)) {
                throw new \Exception();
            }
        } catch (\Exception $e) {
            $result = static::DEFAULT_TOTAL_USERS;
        }

        return $result;
    }

    public static function getTotalOnlineUsers(): int
    {
        try {
            $result = \IPS\Db::i()->select('count(*)', 'core_sessions', 'running_time > unix_timestamp(date_sub(now(), interval 60 minute))')->first();

            if (!Is::Integer($result)) {
                throw new \Exception();
            }
        } catch (\Exception $e) {
            $result = static::DEFAULT_TOTAL_ONLINE_USERS;
        }

        return $result;
    }

    public static function getTotalPostings(): int
    {
        $table_prefix = \IPS\Db::i()->prefix;

        $sql_query = 'select (select count(*) from ' . $table_prefix . 'forums_posts) + (select count(*) from ' . $table_prefix . 'forums_archive_posts) as total';

        try {
            $stmt = \IPS\Db::i()->query($sql_query);

            $result = $stmt->fetch_array(\MYSQLI_ASSOC);

            $result = $result['total'];
        } catch (\UnderflowException $e) {
        }

        if (!isset($result) || !Is::Integer($result)) {
            $result = static::DEFAULT_TOTAL_POSTINGS;
        }

        return $result;
    }

    public static function callFunctionOnMember(int $user_id, string $function, ...$args)
    {
        $member = \IPS\Member::load($user_id);

        if (isset($member->member_id) && ($member->member_id > 0)) {
            $callable = [$member, $function];

            if (is_callable($callable)) {
                return call_user_func_array($callable, $args);
            }
        }

        return null;
    }

    public static function getMemberPostActivity(int $user_id, int $limit = 25): array
    {
        $search_as_member = \IPS\Member::load(0); // guest
        $member = \IPS\Member::load($user_id);

        $activity = [];
        $pageset = 1;

        while (count($activity) < $limit) {
            $query = \IPS\Content\Search\Query::init($search_as_member)->filterByContent([\IPS\Content\Search\ContentFilter::init('IPS\\core\\Statuses\\Status')], false)->filterByAuthor($member)->setOrder(\IPS\Content\Search\Query::ORDER_NEWEST_UPDATED)->setPage($pageset);
            $results = $query->search();

            if (empty($results)) {
                break;
            }

            foreach ($results as $r) {
                $r_array = $r->asArray();

                if (!array_key_exists($r_array['indexData']['index_item_id'], $activity)) {
                    $class = $r_array['indexData']['index_class'];
                    $object = $class::load($r_array['indexData']['index_object_id']);

                    $activity[$r_array['indexData']['index_item_id']] = [
                        'title' => $r_array['itemData']['title'],
                        'url' => (string)$object->url(),
                        'posts' => $r_array['itemData']['posts'],
                        'posts_formatted' => number_format($r_array['itemData']['posts'])
                    ];
                }

                if (count($activity) >= $limit) {
                    break 2;
                }
            }

            $pageset++;
        }

        return $activity;
    }

    public static function getMemberFollowing(int $user_id, ?int $limit = 6): array
    {
        $result = [];

        $where = [
            [
                'f.follow_app=?',
                'core'
            ],
            [
                'f.follow_area=?',
                'member'
            ],
            [
                'f.follow_member_id=?',
                $user_id
            ],
            [
                'f.follow_is_anon=?',
                0
            ],
            [
                'f.follow_visible=?',
                1
            ],
            [
                'f.follow_rel_id = m.member_id'
            ],
            [
                'm.temp_ban = ?',
                0
            ],
            [
                'm.restrict_post = ?',
                0
            ],
            [
                'm.mod_posts = ?',
                0
            ]
        ];

        $where[] = [
            '((m.member_group_id in (' . Users::GROUP_ADMIN_ID . ',' . Users::GROUP_TEAM_CORE_ID . ',' . Users::GROUP_TEAM_COMMUNITY_ID . ',' . Users::GROUP_AMBASSADOR_ID . ',' . Users::GROUP_PARTNER_ID . ')) OR (' . \IPS\Db::i()->findInSet('m.mgroup_others', [Users::GROUP_ADMIN_ID, Users::GROUP_TEAM_CORE_ID, Users::GROUP_TEAM_COMMUNITY_ID, Users::GROUP_AMBASSADOR_ID, Users::GROUP_PARTNER_ID]) . '))',
        ];

        try {
            $result = \IPS\Db::i()->select('f.follow_rel_id', [['core_follow', 'f'], ['core_members', 'm']], $where, 'f.follow_added desc', $limit);

            $result = iterator_to_array($result);
        } catch (\UnderflowException $e) {
        }

        if (!is_array($result)) {
            $result = [];
        }

        return $result;
    }

    public static function getForumChannelUrl(int $id)
    {
        return (string)\IPS\forums\Forum::load($id)->url();
    }

    public static function getForumClubUrl(int $id)
    {
        return (string)\IPS\Member\Club::load($id)->url();
    }
}
