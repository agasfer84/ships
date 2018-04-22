<?php

class Database extends PDO
{

    protected static $_instance = null;

    protected $db_host = 'server29.hosting.reg.ru';
    protected $db_name = 'u0835050_s_base';
    protected $db_user = 'u0835050_ships';
    protected $db_pass = 'ships@1234';

    public function __construct() {
        return $this->connection();
    }


    public function connection() {

        try {
            parent::__construct("mysql:host={$this->db_host};dbname={$this->db_name};charset=utf8", $this->db_user, $this->db_pass);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

    }


        /*$dsn = 'mysql:host=server29.hosting.reg.ru;dbname=u0835050_s_base;charset=utf8';
        $username = 'u0835050_ships';
        $password = 'ships@1234';

        $table = 'ships';
        $connection = new PDO($dsn, $username, $password, array(
            PDO::ATTR_PERSISTENT => true
        ));


        if ($connection) {
            //echo "<div style=\"display:none\">Соединение установлено</div>";
        } else {
            return "Ошибка подключения к базе данных";
        }
        return $connection;
    }*/
}
?>