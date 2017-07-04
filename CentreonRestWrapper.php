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
include_once 'conf.php';
class CentreonRestWrapper {
    
    private $username=Config::username;
    private $password=Config::password;
    private $CentreonIp=Config::CentreonIp;
    private $isHTTPS=Config::isHTTPS;

    private $baseUrl="";
    private $sUrl="";
    private $authToken="";
    private $debug=True;
    
    function __construct(){
        if($this->isHTTPS){
            $this->baseUrl='https://'.$this->CentreonIp.'/centreon/api/index.php?action=';
        }else{
            $this->baseUrl='http://'.$this->CentreonIp.'/centreon/api/index.php?action=';
        }
    }
    
    function Authenticate(){
        $post_data=array();
        $this->sUrl=$this->baseUrl.'authenticate';
        if($this->debug){
            print "\n";
            print "Authentication :\n";
            print "URL = ".$this->sUrl."\n";
            print "User = ".$this->username."\n";
            print "Password = ".$this->password."\n";
            print "\n";
        }
        $post_data['username']=$this->username;
        $post_data['password']=$this->password;

        $jsonResult=$this->SendRequest("", "", $post_data);
        $this->authToken=$jsonResult["authToken"];
        if($this->debug){
            print "\n";
            print "AuthToken = ".$this->authToken."\n";
            print "\n";
            print "\n--------------------------------------\n";
            
        }
        
    }
    
    function DoPostRequest_curl($aData, $header_data, $post_data){
            if($post_data==array()){
                $post_data=json_encode($aData);
            }
            $curl = curl_init($this->sUrl);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);

            if($header_data!=""){
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header_data);
            }
            if($this->debug){
                print "\n";
                print_r($post_data);
                print "\n";
            }
            
            $response = curl_exec($curl);
            curl_close($curl);
            return $response;
    }
    function extractJson($json){
        $aResults = json_decode($json,True);
        if ($aResults){
            if($this->debug){
		echo "\n--------------------------------------\n";
		echo "Reply : \n";
                echo "\n";
		print_r($aResults);
                echo "\n";
            }
            
            return $aResults;
	}
	else{
            if($this->debug){
		echo "ERROR ".$this->sUrl." replied:\n";
                print '\n';
		print_r($json);
                print '\n';
            }else{
                echo "JSON/Rest error !\n>";
                exit();
            }
	}
    }
    
    function SendRequest($json_array, $header_data="", $post_data=array()){
        return $this->extractJson($this->DoPostRequest_curl($json_array, $header_data, $post_data));
    }
    
    function ClapiCall($action, $object, $clapiLine){
        $this->sUrl=$this->baseUrl."action&object=centreon_clapi";
        $header_data=array(
            'Content-Type: application/json',
            'centreon-auth-token: '.$this->authToken
        );
        
        $json_data = array(
            'action' => $action,
            'object' => $object,
            'values' => $clapiLine,
	);
        if($this->debug){
            print "Add host : \n";
            print 'URL = '.$this->sUrl."\n";
            print 'Token = '.$this->authToken."\n";
            print "Data : \n";
            print_r($json_data);
            print "Head Data : \n";
            print_r($header_data);
        }

        $jsonResult=$this->SendRequest($json_data, $header_data);
        print "Response \n";
        print_r($jsonResult);
        print "\n";
        return $jsonResult;
    }
            
    function InsertHost($hostName, $hostAlias, $hostIp, $hostTemplate, $hostGroups="",$poller="central"){
        $action="add";
        $object="host";
        $clapiLine=$hostName.";".$hostAlias.";".$hostIp.";".$hostTemplate.";".$poller.";";
        if($hostGroups != ""){
            $clapiLine .= $hostGroups;
        }
        $this->ClapiCall($action, $object, $clapiLine);
    }
    
    function ApplyTemplate($hostName){  
        $action="applytpl";
        $object="host";
        $clapiLine=$hostName;
        $this->ClapiCall($action, $object, $clapiLine);
    }
    
    
   
}
?>
