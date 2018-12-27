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

        $query = "SELECT s.*, f.force_name FROM ships s LEFT JOIN forces f on f.id = s.force_id  WHERE s.country=:country AND s.isactive=1 ORDER BY s.order_id";
        $ships = $connection->prepare($query);
        $ships->setFetchMode(PDO::FETCH_ASSOC);
        $ships->execute(array("country" => $country));

        //return $ships->fetchAll(PDO::FETCH_ASSOC);
        $forces = [];
        $forces_multiarr = [];

        foreach ($ships as $ship) {
            $forces[$ship["force_name"]][] = $ship;
        }

        foreach ($forces as $key => $value) {
            $forces_multiarr[] = ["force_name" => $key, "force_ships" => $value];
        }

        return $forces_multiarr;
    }

    public function getForcesList()
    {
        $connection = $this->db;
        $country = $this->getSides()["player"];
        $query = "SELECT * FROM forces WHERE country=:country";
        $forces = $connection->prepare($query);
        $forces->setFetchMode(PDO::FETCH_ASSOC);
        $forces->execute(array("country" => $country));

        return $forces->fetchAll(PDO::FETCH_ASSOC);

    }

}