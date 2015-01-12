<?php

class User {
    public $id;
    public $name;
    public $email;
    public $passHash;
    public $avatar;
    public $token;

    public $created;

    /**
     * @var $dbAdapter \PDO
     */
    public static $dbAdapter;

    public function __construct($id, $name, $email, $passHash, $avatar, $token)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->passHash = $passHash;
        $this->avatar = $avatar;
        $this->token = $token;
    }
    public function login($rememberMe = false)
    {
        $this->token = md5(uniqid().$this->passHash);
        $_SESSION['token'] = $this->token;
        if($rememberMe) setcookie('token', $this->token, time()+(60*60*24*7));
        $this->save();
    }
    public function logout()
    {
        $this->token = null;
        setcookie ('token', "", time() - 3600);
        if(array_key_exists('token', $_SESSION)){
            unset($_SESSION['token']);
        }
        $this->save();
    }
    public function save()
    {
        if($this->id){
            static::$dbAdapter->exec(
                'UPDATE Users
                SET
                  name='.static::quote($this->name).',
                  email='.static::quote($this->email).',
                  passHash='.static::quote($this->passHash).',
                  avatar='.static::quote($this->avatar).',
                  token='.($this->token?static::quote($this->token):'NULL').'
                WHERE id = '.static::quote($this->id).';'
            );
        }else{
            $execResult = static::$dbAdapter->exec(
                'INSERT INTO Users (name, email, passHash, avatar, token)
                VALUES ('.
                    static::quote($this->name).','.
                    static::quote($this->email).','.
                    static::quote($this->passHash).','.
                    static::quote($this->avatar).','.
                    ($this->token?static::quote($this->token):'NULL') .''
            .');'
            );
            if($execResult){
                $this->id = static::$dbAdapter->lastInsertId();
                $this->created = static::$dbAdapter->query(
                    'SELECT created FROM Users WHERE id = '.
                    static::quote($this->id).';'
                )->fetch(\PDO::FETCH_ASSOC)['created'];
            }else{
                throw new ErrorException(
                    static::$dbAdapter->errorInfo()[2],
                    static::$dbAdapter->errorCode()
                );
            }
        }
        return $this;
    }
    public function delete()
    {
        static::$dbAdapter->exec('DELETE FROM Users WHERE id='.static::quote($this->id).';');
    }
    public static function getById($id)
    {
        $id = (int) $id;
        $userData = static::$dbAdapter->query(
            'SELECT * FROM Users WHERE id='
            .static::quote($id).
            ' LIMIT 1;'
        )->fetch(\PDO::FETCH_ASSOC);
        if($userData){
            return new static(
                $userData['id'], $userData['name'], $userData['email'],
                $userData['passHash'], $userData['avatar'], $userData['token']
            );
        }
        return false;
    }
    public static function getByToken($token)
    {
        $token = (string) $token;
        $userData = static::$dbAdapter->query(
            'SELECT * FROM Users WHERE token='
            .static::quote($token).
            ' LIMIT 1;'
        )->fetch(\PDO::FETCH_ASSOC);
        if($userData){
            return new static(
                $userData['id'], $userData['name'], $userData['email'],
                $userData['passHash'], $userData['avatar'], $userData['token']
            );
        }
    }
    public static function getByCredentials($email, $password)
    {
        $email = (string) $email;
        $password = (string) $password;
        $userData = static::$dbAdapter->query(
            'SELECT * FROM Users WHERE
              email ='.static::quote($email).'
            LIMIT 1;'
        )->fetch(\PDO::FETCH_ASSOC);
        if(password_verify($password, $userData['passHash'])){
            return new static(
                $userData['id'], $userData['name'], $userData['email'],
                $userData['passHash'], $userData['avatar'], $userData['token']
            );
        }
        return false;
    }
    public static function createFromArray(array $userData)
    {
        $userData['passHash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        $user = new static(
            null, $userData['name'], $userData['email'],
            $userData['passHash'], $userData['avatar'], null
        );
        $user->save();
        return $user;
    }
    protected static function quote($string)
    {
        return static::$dbAdapter->quote($string);
    }
    public function hydrate($userData)
    {
        $this->id = $userData['id'];
        $this->name = $userData['name'];
        $this->email = $userData['email'];
        $this->passHash = $userData['passHash'];
        $this->avatar = $userData['avatar'];
        $this->token = $userData['token'];
        $this->created = $userData['created'];
        return $this;
    }
    public function extract()
    {
        $userData = [];
        $userData['id'] = $this->id;
        $userData['name'] = $this->name;
        $userData['email'] = $this->email;
        $userData['passHash'] = $this->passHash;
        $userData['avatar'] = $this->avatar;
        $userData['token'] = $this->token;
        $userData['created'] = $this->created;
        return $userData;
    }
}

?>
