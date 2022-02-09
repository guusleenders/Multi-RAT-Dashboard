<?php

define("GENERAL_DEVICEID_POS",       0);
define("GENERAL_BOOTID_POS",         1);

define("NBIOT_PACKETNUMBER_POS",     2);
define("NBIOT_ENERGY_POS",           3);
define("NBIOT_CONDITIONS_POS",       4);
define("NBIOT_INITSTATUS_POS",       5);
define("NBIOT_CLOSESTATUS_POS",      6);
define("NBIOT_CLOSETIME_POS",        7);
define("NBIOT_PAYLOADSIZE_POS",      8);

define("SIGFOX_PACKETNUMBER_POS",    9);
define("SIGFOX_ENERGY_POS",          10);
define("SIGFOX_CONDITIONS_POS",      11);
define("SIGFOX_INITSTATUS_POS",      12);
define("SIGFOX_PAYLOADSIZE_POS",     13);

define("LORAWAN_PACKETNUMBER_POS",   14);
define("LORAWAN_ENERGY_POS",         15);
define("LORAWAN_CONDITIONS_POS",     16);
define("LORAWAN_INITSTATUS_POS",     17);
define("LORAWAN_PAYLOADSIZE_POS",    18);


define("NBIOT_CELEVEL_POS",          0);
define("NBIOT_CEREG_POS",            1);
define("NBIOT_QPSMS_POS",            2);
define("NBIOT_CEDRXRDP_POS",         3);
define("NBIOT_QCSQ_POS",             4);

define("LORAWAN_DATARATE_POS",       0);
define("LORAWAN_POWER_POS",          1);
define("LORAWAN_ADR_POS",            2);

$requestType = $_SERVER['REQUEST_METHOD'];




/* Attempt MySQL server connection. Assuming you are running MySQL
server with default setting (user 'root' with no password) */
define("MYSQL_SERVERNAME",      "dramco.be.mysql");
define("MYSQL_USERNAME",        "dramco_be_guus");
define("MYSQL_DBNAME",          "dramco_be_guus");
define("MYSQL_PASSWORD",        "pZHCu5DjPHueLC9n");

switch ($requestType) {
    case 'POST':
        handlePostRequest();
        break;
    case 'GET':
        handleGetRequest();  
        break;
    default:
        //request type that isn't being handled.
        break;
}

function handlePostRequest(){
    if(isset($_POST["measurement"]) && isset($_POST["time"])){
        
        // Check connection
        $conn = mysqli_connect(MYSQL_SERVERNAME, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        // Check connection
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }

        $measurement = str_getcsv($_POST["measurement"], ";");
        
        //0,58,94,30345,0|+CEREG: 1,"71F6","14BCB0C",9,,,"00001010","00001010"|+QPSMS: 1,,,"6000","20"|+CEDRXRDP: 0|,0,0,196,94,3400,14|,1,0,94,575,,1,0,
        
        // - Get current device properties
        $row = 1;
        $general_location = "";
        $general_moving = 0;
        $general_coordinates = "";
        $nbiot_network = "";
        $lorawan_network = "";
        
        $sql = "SELECT * FROM `properties` WHERE `id`='".$measurement[GENERAL_DEVICEID_POS]."';";
        $result = mysqli_query($conn, $sql);
        
        if($result){
            echo "Records executed successfully.";
        }else{
            echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
        }
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            print_r($row);
            $general_location = $row["general_location"];
            $general_level = $row["general_level"];
            $general_environment = $row["general_environment"];
            $general_moving = intval($row["general_moving"]);
            if($general_moving <= 0)
                $general_coordinates = $row["general_coordinates"];
            $nbiot_network = $row["nbiot_network"];
            $lorawan_network = $row["lorawan_network"];
        }else{
            echo "Nothing found.";
        }
        
        /*if (($handle = fopen("deviceProperties.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 500, ",")) !== FALSE) {
                if($data[0] == $measurement[GENERAL_DEVICEID_POS]){
                    $general_location = $data[1];
                    $nbiot_network = $data[2];
                    $lorawan_network = $data[3];
                    
                }
            }
            fclose($handle);
        }*/
        
        $time_min = $_POST["time"]-3*60*1000;
        $time_max = $_POST["time"]+3*60*1000;
        $lorawan_callback = 0;
        $sigfox_callback = 0;
        $nbiot_callback = 0;
        
        //-- Search for NB-IoT callback
        $sql = "SELECT `id` FROM nbiot WHERE `general_bootID`=".$measurement[GENERAL_BOOTID_POS]." AND `general_deviceID`=".$measurement[GENERAL_DEVICEID_POS]." AND `nbiot_packetNumber`=".$measurement[NBIOT_PACKETNUMBER_POS]." AND `time` BETWEEN $time_min AND $time_max;";
        $result = mysqli_query($conn, $sql);
        
        if($result){
            echo "Records executed successfully.";
        }else{
            echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
        }
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $nbiot_callback = $row["id"];
        }else{
            echo "Nothing found.";
        }
        
        //-- Search for LoRaWAN callback
        $sql = "SELECT `id` FROM lorawan WHERE `general_bootID`=".$measurement[GENERAL_BOOTID_POS]." AND `general_deviceID`=".$measurement[GENERAL_DEVICEID_POS]." AND `lorawan_packetNumber`=".$measurement[LORAWAN_PACKETNUMBER_POS]." AND `time` BETWEEN $time_min AND $time_max;";
        $result = mysqli_query($conn, $sql);
        
        if($result){
            echo "Records executed successfully.";
        }else{
            echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
        }
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $lorawan_callback = $row["id"];
        }else{
            echo "Nothing found.";
        }
        
        //-- Search for Sigfox callback
        $sql = "SELECT `id` FROM sigfox WHERE `general_bootID`=".$measurement[GENERAL_BOOTID_POS]." AND `general_deviceID`=".$measurement[GENERAL_DEVICEID_POS]." AND `sigfox_packetNumber`=".$measurement[SIGFOX_PACKETNUMBER_POS]." AND `time` BETWEEN $time_min AND $time_max;";
        $result = mysqli_query($conn, $sql);

        if($result){
            echo "Records executed successfully.";
        }else{
            echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
        }
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $sigfox_callback = $row["id"];
        }else{
            echo "Nothing found.";
        }
        
        echo("NB-IoT Callback: ".$sigfox_callback);
        echo("LoRaWAN Callback: ".$lorawan_callback);
        echo("Sigfox Callback: ".$sigfox_callback);
               
        // Attempt insert query execution
        $sql = "INSERT INTO `multi-rat` ( `time`, `general_deviceID`, `general_bootID`, `general_location`, `general_moving`, `general_level`, `general_environment`,`general_coordinates`, 
                                        `nbiot_packetNumber`, `nbiot_energy`, `nbiot_conditions`, `nbiot_initStatus`, `nbiot_closeStatus`, `nbiot_closeTime`, `nbiot_payloadSize`, `nbiot_callback`, `nbiot_network`,
                                        `sigfox_packetNumber`, `sigfox_energy`, `sigfox_conditions`, `sigfox_initStatus`, `sigfox_payloadSize`, `sigfox_callback`, 
                                        `lorawan_packetNumber`, `lorawan_energy`, `lorawan_conditions`, `lorawan_initStatus`, `lorawan_payloadSize`, `lorawan_callback`, `lorawan_network`) 
                            VALUES (    '".$_POST["time"]."', '".$measurement[GENERAL_DEVICEID_POS]."', '".$measurement[GENERAL_BOOTID_POS]."', '".$general_location."', '".$general_moving."', '".$general_level."', '".$general_environment."', '".$general_coordinates."',
                                        '".$measurement[NBIOT_PACKETNUMBER_POS]."', '".$measurement[NBIOT_ENERGY_POS]."', '".$measurement[NBIOT_CONDITIONS_POS]."', '".$measurement[NBIOT_INITSTATUS_POS]."', '".$measurement[NBIOT_CLOSESTATUS_POS]."', '".$measurement[NBIOT_CLOSETIME_POS]."', '".$measurement[NBIOT_PAYLOADSIZE_POS]."', '".$nbiot_callback."', '".$nbiot_network."',
                                        '".$measurement[SIGFOX_PACKETNUMBER_POS]."', '".$measurement[SIGFOX_ENERGY_POS]."', '".$measurement[SIGFOX_CONDITIONS_POS]."', '".$measurement[SIGFOX_INITSTATUS_POS]."', '".$measurement[SIGFOX_PAYLOADSIZE_POS]."','".$sigfox_callback."',
                                        '".$measurement[LORAWAN_PACKETNUMBER_POS]."', '".$measurement[LORAWAN_ENERGY_POS]."', '".$measurement[LORAWAN_CONDITIONS_POS]."', '".$measurement[LORAWAN_INITSTATUS_POS]."', '".$measurement[LORAWAN_PAYLOADSIZE_POS]."','".$lorawan_callback."', '".$lorawan_network."'
                            )";
        if(mysqli_query($conn, $sql)){
            echo "Records inserted successfully.";
        } else{
            echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
        }
        
        mysqli_close($conn);// Close connection
    }else if($_POST['callback'] == 1){
        $json_string = file_get_contents('php://input');
        $myfile = fopen("postNbiot.txt", "w") or die("Unable to open file!");
        fwrite($myfile, $json_string);
        fclose($myfile);
        
        $conn = mysqli_connect(MYSQL_SERVERNAME, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        // Check connection
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        
        $time = $_POST["time"];
        $data = substr($_POST["data"],1);
        $data = str_getcsv($data, ";");
        $general_deviceID = $data[0];
        $general_bootID = $data[1];
        $nbiot_packetNumber = $data[2];
        
            
        $sql = "INSERT INTO nbiot ( `time`,  `general_deviceID`, `general_bootID`, `nbiot_packetNumber`, `nbiot_data`) 
                                VALUES  ( '".$time."', '".$general_deviceID."', '".$general_bootID."', '".$nbiot_packetNumber."', '".$_POST["data"]."'
                    )";
        
        if(mysqli_query($conn, $sql)){
            echo "Records inserted successfully.";
        } else{
            echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
        }
            
        mysqli_close($conn);// Close connection
        
    }else if($_SERVER['HTTP_USER_AGENT'] == "SIGFOX"){
        $json_string = file_get_contents('php://input');
        $myfile = fopen("postSigfox.txt", "w") or die("Unable to open file!");
        fwrite($myfile, $json_string);
        fclose($myfile);
        
        $conn = mysqli_connect(MYSQL_SERVERNAME, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        // Check connection
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        
        $time = $_POST["time"]*1000;
            
        $sql = "INSERT INTO sigfox ( `time`,  `general_deviceID`, `general_bootID`, `sigfox_packetNumber`, `sigfox_device`, `sigfox_deviceTypeID`, `sigfox_seqNumber`, `sigfox_data`) 
                                VALUES  ( '".$time."', '".$_POST["general_deviceID"]."', '".$_POST["general_bootID"]."', '".$_POST["sigfox_packetNumber"]."', '".$_POST["id"]."', '".$_POST["deviceTypeId"]."', '".$_POST["seqNumber"]."', '".$_POST["data"]."'
                    )";
        
        if(mysqli_query($conn, $sql)){
            echo "Records inserted successfully.";
        } else{
            echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
        }
            
        mysqli_close($conn);// Close connection
        
    }else{
        $json_string = file_get_contents('php://input');
        $myfile = fopen("postTTN.txt", "w") or die("Unable to open file!");
        fwrite($myfile, $json_string);
        fclose($myfile);
        
        $json_ = json_decode($json_string, true);
        
        if(isset($json_["uplink_message"])){
            $conn = mysqli_connect(MYSQL_SERVERNAME, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
            // Check connection
            if (mysqli_connect_errno()) {
                echo "Failed to connect to MySQL: " . mysqli_connect_error();
            }
            
            $snip = str_replace(' ', '', $json_string); // remove spaces
            $snip = str_replace("\t", '', $snip); // remove tabs
            $snip = str_replace("\n", '', $snip); // remove new lines
            $snip = str_replace("\r", '', $snip); // remove carriage returns
            $message = stripslashes(mysqli_real_escape_string($conn, $snip));
            
            $general_deviceID = $json_["uplink_message"]["decoded_payload"]["general_deviceID"];
            $general_bootID = $json_["uplink_message"]["decoded_payload"]["general_bootID"];
            $lorawan_packetNumber = $json_["uplink_message"]["decoded_payload"]["lorawan_packetNumber"];
            
            if(isset($json_["uplink_message"]["network"]) && $json_["uplink_message"]["network"]=="proximus"){ // Proximus callback
                $time = $json_["uplink_message"]["Time"];

                $sql = "INSERT INTO lorawan ( `time`,  `general_deviceID`, `general_bootID`, `lorawan_packetNumber`, `network`,`lorawan_deviceEUI`, `lorawan_message`) 
                                    VALUES  ( '".$time."', '".$general_deviceID."', '".$general_bootID."', '".$lorawan_packetNumber."', '".$json_["uplink_message"]["network"]."', '".$json_["uplink_message"]["DevEUI"]."', '".$message."'
                        )";
                        
                if(mysqli_query($conn, $sql)){
                    echo "Records inserted successfully.";
                } else{
                    echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
                }
            } else{ // TTN callback
                $time = substr($json_["received_at"], 0, strpos($json_["received_at"], ".")+3);
                $time = new DateTime($time);
                $time = $time->getTimestamp()*1000;
                
                echo $time;
                
                $sql = "INSERT INTO lorawan ( `time`,  `general_deviceID`, `general_bootID`, `lorawan_packetNumber`,`network`, `lorawan_deviceEUI`, `lorawan_message`) 
                                    VALUES  ( '".$time."', '".$general_deviceID."', '".$general_bootID."', '".$lorawan_packetNumber."', 'ttn', '".$json_["end_device_ids"]["dev_eui"]."', '".$message."'
                        )";
                        
                if(mysqli_query($conn, $sql)){
                    echo "Records inserted successfully.";
                } else{
                    echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
                }
            }
            
            
            mysqli_close($conn);// Close connection
            
        }
        
    }
    
}

function handleGetRequest(){
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');


    if(isset($_GET["values"])){
        $conn = mysqli_connect(MYSQL_SERVERNAME, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        // Check connection
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        $value = stripslashes(mysqli_real_escape_string($conn, $_GET["values"]));
        $sql = "SELECT DISTINCT `$value`  FROM `multi-rat`";
        if (!($result = mysqli_query($conn, $sql))) {
            echo ("Error description: " . mysqli_error($conn)."\n");
        }
        $_row = array();
        while($row = $result->fetch_assoc()){
            array_push($_row, $row[$value]);
        }
        echo json_encode($_row);
        
    }else{
        // Check connection
        $conn = mysqli_connect(MYSQL_SERVERNAME, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        // Check connection
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        
        $limit = 100;
        if(isset($_GET["limit"])){
            $limit = $_GET["limit"];
        }
         
        $where = "";//WHERE `app_id`='$app_id' AND `dev_id`='$dev_id'
        
        $messages = array();
        
        $filterQuery = "";
        if(isset($_GET["filter"])){
            $filterQuery = "WHERE ";
            $filter = json_decode($_GET["filter"], true);

            foreach ($filter as $key => $filterObject) {
                $filterName = stripslashes(mysqli_real_escape_string($conn, $filterObject["name"]));
                $filterValue = stripslashes(mysqli_real_escape_string($conn, $filterObject["value"]));
                $filterMathOperator = stripslashes(mysqli_real_escape_string($conn, $filterObject["math_operator"]));
                $filterRelationOperator = stripslashes(mysqli_real_escape_string($conn, $filterObject["relation_operator"]));
                
                if($filterMathOperator == "eq"){
                    $filterMathOperator = "=";
                }else if($filterMathOperator == "neq"){
                    $filterMathOperator = "!=";
                }else if($filterMathOperator == "leq"){
                    $filterMathOperator = "<";
                }else if($filterMathOperator == "geq"){
                    $filterMathOperator = ">";
                }else{
                    $filterMathOperator = "=";
                }
                
                if($filterRelationOperator == "or"){
                    $filterRelationOperator = "OR";
                }else{
                    $filterRelationOperator = "AND";
                }
                
                if($key > 0)
                    $filterQuery  .=  $filterRelationOperator." `".$filterName."`".$filterMathOperator."'".$filterValue."' ";
                else
                    $filterQuery  .=  "`".$filterName."`".$filterMathOperator."'".$filterValue."' ";
                    
                if($filterName == "general_moving" && $filterValue == "1"){
                    $filterQuery  .=  "AND `general_coordinates`!='' ";
                }
            }
        }
        
        $sql = "SELECT * FROM `multi-rat` $filterQuery ORDER BY `id` DESC LIMIT $limit";
        if (!($result = mysqli_query($conn, $sql))) {
            echo ("Error description: " . mysqli_error($conn)."\n");
        }
        
        while($row = $result->fetch_assoc()){
            $_row = array();
            
            $_row["id"] = (int) $row["id"];
            $_row["time"] = (int) $row["time"];
            $_row["general"]["deviceID"] = (int) $row["general_deviceID"];
            $_row["general"]["bootID"] = (int) $row["general_bootID"];
            $_row["general"]["location"] = $row["general_location"];
            
            $_row["nbiot"]["packetNumber"] = (int) $row["nbiot_packetNumber"];
            $_row["nbiot"]["energy"] = (float) $row["nbiot_energy"]/10;
            
            $nbiot_conditions = str_getcsv($row["nbiot_conditions"], "|");
            $_row["nbiot"]["conditions"] = array();
            $_row["nbiot"]["conditions"]["celevel"] = (int)$nbiot_conditions[NBIOT_CELEVEL_POS];
            
            $nbiot_conditions[NBIOT_CEREG_POS] = str_replace(["\\", "\"", "+CEREG: "], "", $nbiot_conditions[NBIOT_CEREG_POS]);
            $cereg = str_getcsv($nbiot_conditions[NBIOT_CEREG_POS], ",");
            $_row["nbiot"]["conditions"]["cereg"] = array();
            if(count($cereg) == 8){
                $_row["nbiot"]["conditions"]["cereg"]["stat"] = ($cereg[0]==0 ? "NOT REGISTERED" : ($cereg[0]==1 ? "REGISTERED" : ($cereg[0]==2 ? "BUSY" : ($cereg[0]==3 ? "DENIED" : ($cereg[0]==4 ? "UNKNOWN" : ($cereg[0]==5 ? "ROAMING" : ""))))));
                $_row["nbiot"]["conditions"]["cereg"]["tac"] =  $cereg[1];
                $_row["nbiot"]["conditions"]["cereg"]["ci"] =   $cereg[2];
                $_row["nbiot"]["conditions"]["cereg"]["act"] =  ($cereg[3]==9 ? "NBIOT" : ($cereg[3]==8 ? "CATM1" : ($cereg[3]==9 ? "GSM" : "")));
                $_row["nbiot"]["conditions"]["cereg"]["active_time"] = $cereg[6];
                $_row["nbiot"]["conditions"]["cereg"]["periodic_tau"] = $cereg[7];
            }else if(count($cereg) == 5){
                $_row["nbiot"]["conditions"]["cereg"] = array();
                $_row["nbiot"]["conditions"]["cereg"]["stat"] = ($cereg[1]==0 ? "NOT REGISTERED" : ($cereg[1]==1 ? "REGISTERED" : ($cereg[1]==2 ? "BUSY" : ($cereg[1]==3 ? "DENIED" : ($cereg[1]==4 ? "UNKNOWN" : ($cereg[1]==5 ? "ROAMING" : ""))))));
                $_row["nbiot"]["conditions"]["cereg"]["tac"] =  $cereg[2];
                $_row["nbiot"]["conditions"]["cereg"]["ci"] =   $cereg[3];
                $_row["nbiot"]["conditions"]["cereg"]["act"] =  ($cereg[4]==9 ? "NBIOT" : ($cereg[4]==8 ? "CATM1" : ($cereg[4]==9 ? "GSM" : "")));
            }
            $nbiot_conditions[NBIOT_QPSMS_POS] = str_replace(["\\", "\"", "+QPSMS: "], "", $nbiot_conditions[NBIOT_QPSMS_POS]);
            $qpsms = str_getcsv($nbiot_conditions[NBIOT_QPSMS_POS], ",");
            $_row["nbiot"]["conditions"]["qpsms"] = array();
            $_row["nbiot"]["conditions"]["qpsms"]["enabled"] = ($qpsms[0]==1 ? true : false);
            $_row["nbiot"]["conditions"]["qpsms"]["network_periodic_tau"] = (int) $qpsms[3];
            $_row["nbiot"]["conditions"]["qpsms"]["network_active_time"] = (int) $qpsms[4];
            
            $nbiot_conditions[NBIOT_CEDRXRDP_POS] = str_replace(["\\", "\"", "+CEDRXRDP: "], "", $nbiot_conditions[NBIOT_CEDRXRDP_POS]);
            $cedrxrdp = str_getcsv($nbiot_conditions[NBIOT_CEDRXRDP_POS], ",");
            $_row["nbiot"]["conditions"]["cedrxrdp"] = array();
            $_row["nbiot"]["conditions"]["cedrxrdp"]["act_type"] = ($cedrxrdp[0]==5 ? "NBIOT" : ($cedrxrdp[0]==4 ? "CATM1" : ($cedrxrdp[0]==3 ? "UTRAN" : ($cedrxrdp[0]==2 ? "GSM" : ""))));
            $_row["nbiot"]["conditions"]["cedrxrdp"]["requested_edrx_value"] = $cedrxrdp[1];
            $_row["nbiot"]["conditions"]["cedrxrdp"]["network_edrx_value"] = $cedrxrdp[2];
            $_row["nbiot"]["conditions"]["cedrxrdp"]["paging_time_window"] = $cedrxrdp[3];
            
            $nbiot_conditions[NBIOT_QCSQ_POS] = str_replace(["\\", "\"", "+QCSQ: "], "", $nbiot_conditions[NBIOT_QCSQ_POS]);
            $qcsq = str_getcsv($nbiot_conditions[NBIOT_QCSQ_POS], ",");
            $_row["nbiot"]["conditions"]["qcsq"] = array();
            $_row["nbiot"]["conditions"]["qcsq"]["sys_mode"] = $qcsq[0];
            $_row["nbiot"]["conditions"]["qcsq"]["rssi"] = (int) $qcsq[1];
            $_row["nbiot"]["conditions"]["qcsq"]["rsrp"] = (int) $qcsq[2];
            $_row["nbiot"]["conditions"]["qcsq"]["sinr"] = (int) $qcsq[3];
            $_row["nbiot"]["conditions"]["qcsq"]["rsrq"] = (int) $qcsq[4];
            
            $_row["nbiot"]["initStatus"] = ($row["nbiot_initStatus"]==0 ? "POWERDOWN" : ($row["nbiot_initStatus"]==1 ? "ACTIVE" : ($row["nbiot_initStatus"]==2 ? "PSM" : ($row["nbiot_initStatus"]==3 ? "ERROR" : ""))));
            $_row["nbiot"]["closeStatus"] = ($row["nbiot_closeStatus"]==0 ? "POWERDOWN" : ($row["nbiot_closeStatus"]==1 ? "ACTIVE" : ($row["nbiot_closeStatus"]==2 ? "PSM" : ($row["nbiot_closeStatus"]==3 ? "ERROR" : ""))));
            $_row["nbiot"]["payloadSize"] = (int) $row["nbiot_payloadSize"];
            $_row["nbiot"]["closeTime"] = (int) $row["nbiot_closeTime"];
            
            $_row["nbiot"]["callback"] = (int) $row["nbiot_callback"];
            
            $_row["sigfox"]["packetNumber"] = (int) $row["sigfox_packetNumber"];
            $_row["sigfox"]["energy"] = (float) $row["sigfox_energy"]/10;
            
            $sigfox_conditions = str_getcsv($row["sigfox_conditions"], "|");
            $_row["sigfox"]["conditions"]["power"] = (int) $sigfox_conditions[0];
            $_row["sigfox"]["initStatus"] = ($row["sigfox_initStatus"]==0 ? "POWERDOWN" : ($row["sigfox_initStatus"]==1 ? "ACTIVE" : ($row["sigfox_initStatus"]==2 ? "PSM" : ($row["sigfox_initStatus"]==3 ? "ERROR" : ""))));
            $_row["sigfox"]["payloadSize"] = (int) $row["sigfox_payloadSize"];
            
            $_row["sigfox"]["callback"] = (int) $row["sigfox_callback"];
            
            $_row["lorawan"]["packetNumber"] = (int) $row["lorawan_packetNumber"];
            $_row["lorawan"]["energy"] = (float) $row["lorawan_energy"]/10;
            
            $lorawan_conditions = str_getcsv($row["lorawan_conditions"], "|");;
            $_row["lorawan"]["conditions"]["data_rate"] = ($lorawan_conditions[LORAWAN_DATARATE_POS]==0 ? "SF12BW125" : ($lorawan_conditions[LORAWAN_DATARATE_POS]==1 ? "SF11BW125" : ($lorawan_conditions[LORAWAN_DATARATE_POS]==2 ? "SF10BW125" : ($lorawan_conditions[LORAWAN_DATARATE_POS]==3 ? "SF9BW125" : ($lorawan_conditions[LORAWAN_DATARATE_POS]==4 ? "SF8BW125" : ($lorawan_conditions[LORAWAN_DATARATE_POS]==5 ? "SF7BW125" : ""))))));
            $_row["lorawan"]["conditions"]["power"] = $lorawan_conditions[LORAWAN_POWER_POS];
            $_row["lorawan"]["conditions"]["adr"] = $lorawan_conditions[LORAWAN_ADR_POS]==1;
            $_row["lorawan"]["initStatus"] = ($row["lorawan_initStatus"]==0 ? "POWERDOWN" : ($row["lorawan_initStatus"]==1 ? "ACTIVE" : ($row["lorawan_initStatus"]==2 ? "PSM" : ($row["lorawan_initStatus"]==3 ? "ERROR" : ""))));
            $_row["lorawan"]["payloadSize"] = (int) $row["lorawan_payloadSize"];
            
            $_row["lorawan"]["callback"] = (int) $row["lorawan_callback"];
            
            array_push($messages, $_row);
        }
        
        $row_json = json_encode($messages);
        echo $row_json;
        
        mysqli_close($conn);// Close connection
    }
}


?>