<?php

/*
 * Copyright (C) 2017 christophe
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of GLPIDBWrapper
 *
 * @author christophe
 */
include_once 'conf.php';
class GLPIDBWrapper {
    private $OS_TEMPLATE=array(
        "CentOS" => "OS-Linux-SNMP-custom",
        "Windows" => "OS-Windows-SNMP-custom",
        "Cisco" => "Net-Cisco-Standard-SNMP-custom",
        "Default" => "generic-active-host-custom"
    );
    private $GLPIDBIp=Config::GLPIDBIp;
    private $GLPIDBUser=Config::GLPIDBUser;
    private $GLPIDBPass=Config::GLPIDBPass;
    private $GLPIDBName=Config::GLPIDBName;
    private $GLPISQLQuery='(
SELECT cp.name AS HostName,  ip.name AS IP, ip.mainitemtype AS TYPE, op.name AS OS
FROM glpi_computers cp 
LEFT JOIN glpi_operatingsystems op ON cp.operatingsystems_id = op.id
JOIN glpi_ipaddresses ip ON (cp.id = ip.mainitems_id AND ip.binary_2 = 65535 AND ip.name !="127.0.0.1" AND ip.mainitemtype ="Computer") LIMIT 1
)
UNION
(
SELECT net.name AS HostName,  ip.name AS IP, ip.mainitemtype AS TYPE, CONCAT(manufacturer.name," ",model.name) AS OS
FROM glpi_networkequipments net
LEFT JOIN glpi_networkequipmentmodels model ON net.networkequipmentmodels_id=model.id
LEFT JOIN glpi_manufacturers manufacturer ON net.manufacturers_id=manufacturer.id
JOIN glpi_ipaddresses ip ON (net.id = ip.mainitems_id AND ip.mainitemtype = "NetworkEquipment" AND ip.binary_2 = 65535 AND ip.name !="127.0.0.1") LIMIT 1
)';
    
    private $bdd;
    private $hostList;
    
    function __construct() {
        try {
            $this->bdd = new PDO('mysql:host='.$this->GLPIDBIp.';dbname='.$this->GLPIDBName.';charset=utf8', $this->GLPIDBUser, $this->GLPIDBPass);
            $this->GetHosts();
        } catch (Exception $e) {
            die('Erreur : ' . $e->getMessage());
        }
    }

    private function GetHosts(){
        $reponse = $this->bdd->query($this->GLPISQLQuery);
        $this->hostList=array();
        $this->hostList["Network"]=array();
        $this->hostList["Computer"]=array();
        $this->hostList["Other"]=array();
        while($donnees=$reponse->fetch()){
            switch($donnees["TYPE"]){            
                case "NetworkEquipement":
                    $this->hostList["Network"][$donnees["HostName"]]=array();
                    $this->hostList["Network"][$donnees["HostName"]]["IP"]=$donnees["IP"];
                    $this->hostList["Network"][$donnees["HostName"]]["Template"]=$this->getTemplate($donnees["OS"]);
                    break;
                case "Computer":
                    $this->hostList["Computer"][$donnees["HostName"]]=array();
                    $this->hostList["Computer"][$donnees["HostName"]]["IP"]=$donnees["IP"];
                    $this->hostList["Computer"][$donnees["HostName"]]["Template"]=$this->getTemplate($donnees["OS"]);
                    break;
                default:
                    $this->hostList["Other"][$donnees["HostName"]]=array();
                    $this->hostList["Other"][$donnees["HostName"]]["IP"]=$donnees["IP"];
                    $this->hostList["Other"][$donnees["HostName"]]["Template"]=$this->getTemplate($donnees["OS"]);
                    break;
            }
        }
        $reponse->closeCursor();
    }
    
    private function getTemplate($OS)
    {
        foreach ($this->OS_TEMPLATE as $regex => $template) {
            if(preg_match("/.*".$regex."*/", $OS) == 1){
                return $template;
            }
        }
        return $this->OS_TEMPLATE["Default"];
    }
    
    function GetComputer(){
        return $this->hostList["Computer"];
    }
    
    function GetNetwork(){
        return $this->hostList["Network"];
    }
    
    function GetOther(){
        return $this->hostList["Other"];
    }
}


?>