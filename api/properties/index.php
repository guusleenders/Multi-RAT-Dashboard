<?php

$requestType = $_SERVER['REQUEST_METHOD'];

define("CSV_TIMESTAMP_POS",           0);
define("CSV_LATITUDE_POS",            1);
define("CSV_LONGITUDE_POS",           2);
define("CSV_ALTITUDE_POS",            3);
define("CSV_HORIZONTAL_ACCURACY_POS", 4);
define("CSV_VERTICAL_ACCURACY_POS",   5);
define("CSV_SPEED_POS",               6);
define("CSV_COURSE_POS",              7);


$csvDelimiter = array(";",",");

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
    $success = false;
    $conn = mysqli_connect(MYSQL_SERVERNAME, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
    // Check connection
    if (mysqli_connect_errno()) {
        echo '{"success":false,"error":"Failed to connect to MySQL. ' . mysqli_connect_error() . '"}';
    }
        
    if(isset($_POST["id"]) && isset($_POST["general_location"]) && isset($_POST["nbiot_network"])  && isset($_POST["lorawan_network"])  && isset($_POST["general_moving"])  && isset($_POST["general_coordinates"])  ){
        // TODO NOT TESTED
        $sql = "INSERT INTO `properties` ( `id`, `general_location`, `nbiot_network`, `lorawan_network`, `general_moving`, `general_level`, `general_environment`, `general_coordinates`) 
                                  VALUES ( '".$_POST["id"]."', '".$_POST["general_location"]."', '".$_POST["nbiot_network"]."', '".$_POST["lorawan_network"]."', '".$_POST["general_moving"]."', '".$_POST["general_level"]."', '".$_POST["general_environment"]."', '".$_POST["general_coordinates"]."') ON DUPLICATE KEY UPDATE `general_location`=VALUES(`general_location`),`nbiot_network`=VALUES(`nbiot_network`),`lorawan_network`=VALUES(`lorawan_network`),`general_moving`=VALUES(`general_moving`),`general_level`=VALUES(`general_level`),`general_environment`=VALUES(`general_environment`),`general_coordinates`=VALUES(`general_coordinates`)";
        if(mysqli_query($conn, $sql)){
            
        } else{
            echo '{"success":false,"error":"Could not able to execute $sql. ' .mysqli_error($conn) . '"}';
            exit();
        }
        
        mysqli_close($conn);// Close connection
        
    }
    
    if(isset($_POST["general_moving"]) && $_POST["general_moving"] == 1 &&  isset($_FILES["fileToUpload"]) && basename($_FILES["fileToUpload"]["name"]) != ""){
        
        // Upload and save CSV file
        $target_dir = "uploads/";
        $target_file = $target_dir . time() . basename($_FILES["fileToUpload"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        
        
        // Check if file already exists
        if (file_exists($target_file)) {
            echo '{"success":false,"error":"Sorry, file already exists."}';
            exit();
            $uploadOk = 0;
        }
        
        // Check file size
        if ($_FILES["fileToUpload"]["size"] > 500000) {
            echo '{"success":false,"error":"Sorry, your file is too large."}';
            exit();
            $uploadOk = 0;
        }
        
        // Allow certain file formats
        if($imageFileType != "csv" && $imageFileType != "txt" ) {
            echo '{"success":false,"error":"Sorry, only CSV files are allowed."}';
            exit();
            $uploadOk = 0;
        }
        
        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            echo '{"success":false,"error":"Sorry, your file was not uploaded."}';
            exit();
            // if everything is ok, try to upload file
        } else {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                
            } else {
                echo '{"success":false,"error":"Sorry, there was an error uploading your file."}';
                exit();
            }
        }  
        
        // Process CSV file
        $deviceID = $_POST["id"];
        $conn = mysqli_connect(MYSQL_SERVERNAME, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
        // Check connection
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        
        $csvPositions = array(
            0 => array(
                "timestamp"             => 0,
                "latitude"              => 1,
                "longitude"             => 2,
                "altitude"              => 3,
                "horizontal_accuracy"   => 4,
                "vertical_accuracy"     => 5,
                "speed"                 => 6,
                "course"                => 7,
                
            ),
            1 => array(
                "timestamp"             => 1,
                "latitude"              => 2,
                "longitude"             => 3,
                "altitude"              => 5,
                "horizontal_accuracy"   => 4,
                "vertical_accuracy"     => 4,
                "speed"                 => 7,
                "course"                => 8,
                
            ));


        replaceDelimiters($target_file);
        if (($handle = fopen($target_file, "r")) !== FALSE) {
            $i = 0; 
            $csvType = 0;
            $affectedRows = 0;
            while (($data = fgetcsv($handle, 500, ";")) !== FALSE) {
                if($i == 0){
                    if($data[0] == "type" && $data[1] == "date time")
                        $csvType = 1;
                }else{
                    $timestamp = strtotime($data[$csvPositions[$csvType]["timestamp"]]);
                    
                    $sendDelay = 300;
                    $sendDelayMultirat = $send_delay/4;
                    
                    $margin = 30;
                    
                    $timestampMin = ($timestamp - $sendDelayMultirat - $margin)*1000;
                    $timestampMax = ($timestamp - $sendDelayMultirat + $margin)*1000;
                    
                    $coordinates = $data[$csvPositions[$csvType]["latitude"]] . "," . $data[$csvPositions[$csvType]["longitude"]];
                    $speed = $data[$csvPositions[$csvType]["speed"]];
                    if($csvType == 1)
                        $speed = $speed*3.6;
                      
                    
                    $movingQuery = "";
                    if(isset($_POST["checkmoving"]))
                        $movingQuery = "`general_moving`=1 AND";
                
                    $sql = "UPDATE `multi-rat` SET `general_coordinates`='".$coordinates."', `general_speed`='".$speed."' WHERE `general_deviceID`=".$deviceID." AND ".$movingQuery."  `time` BETWEEN $timestampMin AND $timestampMax;";
                    
                    $result = mysqli_query($conn, $sql);
            
                    if($result){
                        if(mysqli_affected_rows($conn) > 0)
                            $affectedRows += mysqli_affected_rows($conn);
                    }else{
                        echo '{"success":false,"error":"Could not able to execute $sql. ' . mysqli_error($conn) . $sql . '" }';
                        exit();
                    }
                    
                }
                $i++;
                
            }
            echo '{"success":true,"affectedRows":'.$affectedRows.'}';
            fclose($handle);
        }
        
    }else{
        echo '{"success":true}';
    }
    
}

function handleGetRequest(){
    
    
    
    // Check connection
    $conn = mysqli_connect(MYSQL_SERVERNAME, MYSQL_USERNAME, MYSQL_PASSWORD, MYSQL_DBNAME);
    // Check connection
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
   
    $sql = "SELECT * FROM `properties` ORDER BY `id` ASC";
    if (!($result = mysqli_query($conn, $sql))) {
        echo ("Error description: " . mysqli_error($conn)."\n");
    }
    
    $properties = array();
    while($row = $result->fetch_assoc()){
        array_push($properties, $row);
    }
    
    $row_json = json_encode($properties);
    echo $row_json;
    
    mysqli_close($conn);// Close connection
    
}

function replaceDelimiters($file){
    // Delimiters to be replaced: pipe, comma, semicolon, caret, tabs
    $delimiters = array(',');
    $delimiter = ';';

    $str = file_get_contents($file);
    $str = str_replace($delimiters, $delimiter, $str);
    file_put_contents($file, $str);
}

?>