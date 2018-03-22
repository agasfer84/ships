<?php

class Database
{

    public function __construct() {
        ini_set('mysql.connect_timeout', 300);
        ini_set('default_socket_timeout', 300);
        $this->connection();
    }

    static public function connection()
    {
        $dsn = 'mysql:host=localhost;dbname=s_base';
        $username = 'test';
        $password = 'test';
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
    }
}
?>