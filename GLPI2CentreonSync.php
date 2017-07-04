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

include_once 'CentreonRestWrapper.php';
include_once 'GLPIDBWrapper.php';
$GLPIDB = new GLPIDBWrapper();
$CentreonWrapper=new CentreonRestWrapper();
$clapilist=array();
$CentreonWrapper->Authenticate();


foreach($GLPIDB->GetComputer() as $Hostname => $Data){
    $CentreonWrapper->InsertHost($Hostname, $Hostname, $Data["IP"], $Data["Template"], "App-GLPI2Centreon");
    $CentreonWrapper->ApplyTemplate($Hostname);
}
foreach($GLPIDB->GetNetwork() as $Hostname => $Data){
    $CentreonWrapper->InsertHost($Hostname, $Hostname, $Data["IP"], $Data["Template"], "App-GLPI2Centreon");
    $CentreonWrapper->ApplyTemplate($Hostname);
}
foreach($GLPIDB->GetOther() as $Hostname => $Data){
    $CentreonWrapper->InsertHost($Hostname, $Hostname, $Data["IP"], $Data["Template"], "App-GLPI2Centreon");
    $CentreonWrapper->ApplyTemplate($Hostname);
}

?>