<?php

require 'aws-autoloader.php';

use Aws\Lambda\LambdaClient;

$client = LambdaClient::factory(array(
    'profile' => 'lambda',
    'region'  => 'us-east-1',
    'version' => 'latest'
));

//print_r($client);
$region = "us-east-1";
$number = 1;
$resource_id = "i-0653a9815b3ce93a9";
$description = "";
$accessKey = "AKIAJAOOLN7OXBK2P4EA";
$secretKey = "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";

$params = array(
   "number" => 1,
   "region" => $region,
   "Instance_Id" => $resource_id,
   "Description" => $description,
   "Instance_Name" => $resource_id,
   "accessKey" => $accessKey,
   "secretKey" => $secretKey
);
// lambda function to get all AMI of instance Id
$result = $client->invoke(array(

   'FunctionName' => 'describeAMI',
   'InvocationType' => 'RequestResponse',
   'LogType' => 'Tail',
   'Payload' => json_encode($params)

));

$data = json_decode($result->get('Payload'));
$data = print_r($data,true);
if(!is_array($data)) {
  $data = json_decode($data);
}
//print_r($data->Images[0]->Architecture);
//echo "\nPrinting data....\n";
//print_r($data);die;
$instanceImages = $data->Images;

// Database Setting

$db_hostname = "localhost";
$db_username = "root";
$db_password = "root";
$db_dbname = "cloudportal";

$conn = mysqli_connect($db_hostname, $db_username, $db_password, $db_dbname);

if ($conn->connect_error) {
   die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT retention,exclude_id FROM Resource WHERE resource_id='$resource_id' AND backuptype='AMI'";

$result = $conn->query($sql);

$retention = 0;
$exclude_ids = array();
if($result->num_rows > 0) {
   $row = $result->fetch_assoc();
   $retention = $row["retention"]; // retention days
   $exclude_ids = explode(",", $row['exclude_id']); // exclude_ids array
}


foreach($instanceImages as $instanceImage) {
   
   // AMI description has donotdelete in case insensitive way, then it is not deleted by script
   $todelete = strpos(strtolower($instanceImage->Description),"Do-NOT-DELETE")>-1?false:true;
   if($todelete) {
      if(in_array($instanceImage->ImageId,$exclude_ids)) {
          continue;
      } 
      $creationDate = $instanceImage->CreationDate;
      $currdate = date('Y-m-d');
      $duedate = date('Y-m-d', strtotime($creationDate." + $retention days"));
      $currdate = date('Y-m-d', strtotime('2017-10-27')); // remove it from real code. Just for testing purpose
      echo "instanceImgId: $instanceImage->ImageId ; creation_date: $creationDate ;Curr: $currdate ; Due: $duedate";die;
      if($currdate == $duedate) {
         $params = array(
           "number" => 1,
           "region" => $region,
           "Image_Id" => $instanceImage->ImageId,
           "Description" => $description,
           "Instance_Name" => $resource_id,
           "accessKey" => $accessKey,
           "secretKey" => $secretKey
         );
         // lambda function to delete ami
         $result = $client->invoke(array(

            'FunctionName' => 'delete_ami',
            'InvocationType' => 'RequestResponse',
            'LogType' => 'Tail',
            'Payload' => json_encode($params)
         ));
         $data = json_decode($result->get("Payload"));
         print_r($data);echo "\n";
      }
   }
}
