<?php
require_once($_SERVER['DOCUMENT_ROOT']."/dbconnect.php");
?>
<?php

class Forces
{

    public function __construct()
    {
        $db = new Database();
        $this->db = $db;
    }

    public function getSides()
    {
        $result["player"] = 'russia';
        $result["enemy"] = 'japan';

        return $result;
    }


    public function getShipList()
    {
        $connection = $this->db;

        $country = $this->getSides()["player"];

        $query = "SELECT s.*, f.* FROM ships s LEFT JOIN forces f on f.id = s.force_id  WHERE s.country=:country AND s.isactive=1 ORDER BY s.order_id";
        $ships = $connection->prepare($query);
        $ships->setFetchMode(PDO::FETCH_ASSOC);
        $ships->execute(array("country" => $country));

        //return $ships->fetchAll(PDO::FETCH_ASSOC);
        $forces = [];

        foreach ($ships as $ship) {
            $forces[$ship["force_name"]][] = $ship;
        }

        return $forces;
    }

}