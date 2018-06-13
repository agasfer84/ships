<?php
include $_SERVER['DOCUMENT_ROOT']."/dbconnect.php";
?>
<?php

class Forces
{

    public function __construct()
    {
        $db = new Database();
        $this->db = $db;
    }

    public function getShipList()
    {
        $connection = $this->db;

        $query_rus = "SELECT s.*, f.* FROM ships s LEFT JOIN forces f on f.id = s.force_id  WHERE s.country='russia' AND s.isactive=1 ORDER BY s.order_id";
        $rus_ships = $connection->query($query_rus);
        $rus_ships->setFetchMode(PDO::FETCH_ASSOC);
    }

}