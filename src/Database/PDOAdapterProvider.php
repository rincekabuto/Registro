<?php
namespace Database;

/**
 * Class PDOAdapterProvider
 * @package Database
 */
class PDOAdapterProvider {
    /**
     * @var
     */
    protected static $adapter;

    /**
     * @param \PDO $adapter
     */
    public static function setAdapter(\PDO $adapter){
        static::$adapter = $adapter;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public static function getAdapter(){
        if(isset(static::$adapter)) return static::$adapter;
        else throw new Exception('Adapter requested but not set');
    }
}

?>
