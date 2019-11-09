<?php
namespace App\Model;

use THCFrame\Security\Model\BasicUserModel;
use THCFrame\Core\Lang;
use THCFrame\Security\PasswordManager;
use THCFrame\Core\StringMethods;
Use THCFrame\Core\Rand;

/**
 *
 */
class UserModel extends BasicUserModel
{

    /**
     * Pole uživatelských rolí
     * @var array
     */
    private static $_avRoles = [
        'role_superadmin' => 'Super Admin',
        'role_admin' => 'Admin',
        'role_participant' => 'Člen s přístupem do administrace',
        'role_member' => 'Člen',
    ];

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 40
     *
     * @validate required, alphanumeric, min(3), max(40)
     * @label jméno
     */
    protected $_firstname;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 40
     *
     * @validate required, alphanumeric, min(3), max(40)
     * @label prijmeni
     */
    protected $_lastname;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 15
     * @validate numeric, max(15)
     * @label telefon
     */
    protected $_phoneNumber;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 50
     * @unique
     * @validate alphanumeric, max(50)
     * @label activation token
     */
    protected $_emailActivationToken;

    /**
     * @column
     * @readwrite
     * @index
     * @type tinyint
     * @length 1
     * @default 0
     * @validate max(1)
     */
    protected $_getNewActionNotification;

    /**
     * @column
     * @readwrite
     * @index
     * @type tinyint
     * @length 1
     * @default 0
     * @validate max(1)
     */
    protected $_getNewReportNotification;

    /**
     * @column
     * @readwrite
     * @index
     * @type tinyint
     * @length 1
     * @default 0
     * @validate max(1)
     */
    protected $_getNewNewsNotification;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @default 0
     * @validate max(1)
     * @label limit processing personal data
     */
    protected $_pdLimitProcessing;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @default 0
     * @validate max(1)
     * @label consent to personal data processing
     */
    protected $_pdConsentToProcessing;

    /**
     *
     * @param string $token
     * @return bool
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    private static function checkEmailActToken($token): bool
    {
        $exists = static::first(['emailActivationToken = ?' => $token]);

        return $exists === null;
    }

    /**
     *
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setBlocked(0);
            $this->setDeleted(0);
            $this->setLastLogin(0);
            $this->setTotalLoginAttempts(0);
            $this->setLastLoginAttempt(0);
            $this->setFirstLoginAttempt(0);
            $this->setAccountExpire();
            $this->setPassExipre();
            $this->setForcePassChange(0);
            $this->setPasswordHistory1('');
            $this->setPasswordHistory2('');
            $this->setPdLimitProcessing(0);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     *
     * @return array
     */
    public static function getAllRoles()
    {
        return self::$_avRoles;
    }

    /**
     * @return string
     */
    public function getWholeName()
    {
        return $this->_firstname . ' ' . $this->_lastname;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "Id: {$this->_id} <br/>Email: {$this->_email} <br/> Name: {$this->_firstname} {$this->_lastname}";
    }

    /**
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchAll()
    {
        return self::all(
                ['role <> ?' => 'role_superadmin'], [
                'id', 'firstname', 'lastname', 'email', 'role', 'active', 'created', 'blocked', 'deleted', 'pdLimitProcessing', 'pdConsentToProcessing',
                '(select count(1) from tb_apitoken where tb_apitoken.userId = us.id)' => 'apiTokenCount'
                ], ['id' => 'asc']
        );
    }

    /**
     * @param int $limit
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchLates($limit = 10)
    {
        return self::all(
                ['role <> ?' => 'role_superadmin'], [
                'id', 'firstname', 'lastname', 'email', 'role', 'active', 'created', 'blocked', 'deleted', 'pdLimitProcessing', 'pdConsentToProcessing',
                '(select count(1) from tb_apitoken where tb_apitoken.userId = us.id)' => 'apiTokenCount'
                ], ['created' => 'desc'], (int) $limit
        );
    }

    /**
     * @return array
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchAdminsEmail()
    {
        $admins = self::all(['role = ?' => 'role_admin', 'active = ?' => true, 'deleted = ?' => false, 'blocked = ?' => false], ['email']);

        $returnArr = [];
        if (!empty($admins)) {
            foreach ($admins as $admin) {
                $returnArr[] = $admin->getEmail();
            }
        }

        return $returnArr;
    }

    /**
     *
     * @param string $type
     * @return array|\THCFrame\Model\type
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function getUserEmailsForNotification(string $type)
    {
        if(in_array($type, ['getNewActionNotification', 'getNewReportNotification', 'getNewNewsNotification'])){
            return static::all([$type . ' = ?' => 1, 'active = ?' => 1, 'pdLimitProcessing != ?' => 1], ['email']);
        }
        
        return [];
    }

    /**
     *
     * @param \THCFrame\Bag\BagInterface $post
     * @param array $options
     * @return array
     * @throws \THCFrame\Core\Exception\Lang
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     * @throws \THCFrame\Security\Exception
     */
    public static function createUser(\THCFrame\Bag\BagInterface $post, array $options = []): array
    {
        $errors = [];

        if ($post->get('password') !== $post->get('password2')) {
            $errors['password2'] = [Lang::get('PASS_DOESNT_MATCH')];
        }

        $email = static::first(
                ['email = ?' => $post->get('email')], ['email']
        );

        if ($email) {
            $errors['email'] = [Lang::get('EMAIL_IS_TAKEN')];
        }

        $passStrenght = self::MEMBER_PASS_STRENGHT;
        $minPassLen = self::MEMBER_PASS_LEN;

        if (isset($options['checkForRole']) && $options['checkForRole'] === true) {
            $role = $post->get('role', 'role_member');

            if (in_array($role, ['role_admin', 'role_superadmin'])) {
                $passStrenght = self::ADMIN_PASS_STRENGHT;
                $minPassLen = self::ADMIN_PASS_LEN;
            }
        }

        if (mb_strlen($post->get('password')) < $minPassLen || PasswordManager::strength($post->get('password')) <= $passStrenght) {
            $errors['password'] = [Lang::get('PASS_WEAK')];
        }

        $salt = PasswordManager::createSalt();
        $hash = PasswordManager::hashPassword($post->get('password'), $salt);
        $cleanHash = StringMethods::getHash($post->get('password'));

        if ($options['adminAccountActivation']) {
            $active = false;
        } elseif ($options['verifyEmail']) {
            $active = false;
        } else {
            $active = true;
        }

        $actToken = Rand::randStr(50);
        for ($i = 1; $i <= 75; $i += 1) {
            if (static::checkEmailActToken($actToken)) {
                break;
            } else {
                $actToken = Rand::randStr(50);
            }

            if ($i == 75) {
                $errors['email'] = [Lang::get('UNKNOW_ERROR') . Lang::get('REGISTRATION_FAIL')];
                break;
            }
        }

        $user = new static([
            'firstname' => $post->get('firstname'),
            'lastname' => $post->get('lastname'),
            'email' => $post->get('email'),
            'phoneNumber' => $post->get('phone'),
            'password' => $hash,
            'passwordHistory1' => $cleanHash,
            'salt' => $salt,
            'role' => $role ?? 'role_member',
            'active' => $active,
            'emailActivationToken' => $actToken,
            'getNewActionNotification' => $post->get('actionNotification', 0),
            'getNewReportNotification' => $post->get('reportNotification', 0),
            'getNewNewsNotification' => $post->get('newsNotification', 0),
            'pdConsentToProcessing' => $post->get('pdConsentToProcessing', 0),
            'created' => date('Y-m-d H:i'),
            'modified' => date('Y-m-d H:i'),
        ]);

        return [$user, $errors];
    }

    /**
     *
     * @param \THCFrame\Bag\BagInterface $post
     * @param \App\Model\UserModel $user
     * @param array $options
     * @return array
     * @throws \THCFrame\Core\Exception\Lang
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function editUserProfile(\THCFrame\Bag\BagInterface $post, UserModel $user, array $options = []): array
    {
        $errors = [];

        if ($post->get('password') !== $post->get('password2')) {
            $errors['password2'] = [Lang::get('PASS_DOESNT_MATCH')];
        }

        if ($post->get('email') != $user->email) {
            $email = static::first(
                    ['email = ?' => $post->get('email', $user->email)], ['email']
            );

            if ($email) {
                $errors['email'] = [Lang::get('EMAIL_IS_TAKEN')];
            }
        }

        $oldPassword = $post->get('oldpass');
        $newPassword = $post->get('password');

        if (!empty($oldPassword) && !empty($newPassword)) {
            try {
                $user = $user->changePassword($oldPassword, $newPassword);
            } catch (\THCFrame\Security\Exception\WrongPassword $ex) {
                $errors['oldpass'] = [Lang::get('PASS_ORIGINAL_NOT_CORRECT')];
            } catch (\THCFrame\Security\Exception\WeakPassword $ex) {
                $errors['password'] = [Lang::get('PASS_WEAK')];
            } catch (\THCFrame\Security\Exception\PasswordInHistory $ex) {
                $errors['password'] = [Lang::get('PASS_IN_HISTORY')];
            }
        } elseif (empty($oldPassword) && !empty($newPassword)) {
            $errors['oldpass'] = [Lang::get('PASS_ORIGINAL_NOT_CORRECT')];
        }

        $user->firstname = $post->get('firstname');
        $user->lastname = $post->get('lastname');
        $user->email = $post->get('email');
        $user->phoneNumber = $post->get('phone');
        $user->getNewActionNotification = $post->get('actionNotification', 0);
        $user->getNewReportNotification = $post->get('reportNotification', 0);
        $user->getNewNewsNotification = $post->get('newsNotification', 0);
        $user->pdLimitProcessing = $post->get('pdLimitProcessing', 0);

        return [$user, $errors];
    }

    /**
     *
     * @param int $userId
     * @return boolean
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function deleteUser(int $userId)
    {
        $user = static::first(['id = ?' => $userId]);

        if (!empty($user)) {
            $user->firstname = 'Anonym';
            $user->lastname = 'Anonym';
            $user->email = 'Anonym-' . time() . '@hastrman.cz';
            $user->phoneNumber = '';
            $user->deleted = 1;
            $user->active = 0;
            $user->lastLoginIp = null;
            $user->lastLoginBrowser = null;
            $user->getNewActionNotification = 0;
            $user->getNewReportNotification = 0;
            $user->getNewNewsNotification = 0;
            $user->pdLimitProcessing = 1;
            $user->pdConsentToProcessing = 0;

            if ($user->validate()) {
                $user->save();
                return true;
            }
        }

        return false;
    }
}
