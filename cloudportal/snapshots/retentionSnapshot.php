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

// lambda fn call to get volumes by instance id
$result = $client->invoke(array(

   'FunctionName' => 'describeVolumes',
   'InvocationType' => 'RequestResponse',
   'LogType' => 'Tail',
   'Payload' => json_encode($params)

));

$data = json_decode($result->get('Payload'));
$data = print_r($data,true);
if(!is_array($data)) {
  $data = json_decode($data);
}
//echo "\nPrinting data....\n";
//print_r($data);die;
$volumes = $data->Volumes;
$vol_snapshots = array(); // save snapshots by volume id

foreach($volumes as $volume) {
  
  $params = array(
     "number" => 1,
     "region" => $region,
     "Volume_Id" => $volume->VolumeId,
     "Description" => $description,
     "Instance_Name" => $resource_id,
     "accessKey" => $accessKey,
     "secretKey" => $secretKey
  );
  // Get all snapshots of particular volume
  $result = $client->invoke(array(

     'FunctionName' => 'describeSnapshots',
     'InvocationType' => 'RequestResponse',
     'LogType' => 'Tail',
     'Payload' => json_encode($params)

  ));
  $data = json_decode($result->get('Payload'));
  $data = print_r($data,true);
  if(!is_array($data)) {
     $data = json_decode($data);
  }
  $vol_snapshots[$volume->VolumeId] = $data->Snapshots;
}

//print_r($snapshots);die;

// Database Settings

$db_hostname = "localhost";
$db_username = "root";
$db_password = "root";
$db_dbname = "cloudportal";

$conn = mysqli_connect($db_hostname, $db_username, $db_password, $db_dbname);

if ($conn->connect_error) {
   die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT retention,exclude_id FROM Resource WHERE resource_id='$resource_id' AND backuptype='Snap'";

$result = $conn->query($sql);

$retention = 0;
$exclude_ids = array();
if($result->num_rows > 0) {
   $row = $result->fetch_assoc();
   $retention = $row["retention"]; // retention days
   $exclude_ids = explode(",", $row['exclude_id']); // exclude_ids array
}


foreach($vol_snapshots as $volume => $snapshots) {

  foreach($snapshots as $snapshot) {

     $snapDesc = $snapshot->Description;
     $checkStr = "Created by CreateImage($resource_id) for"; 
     $todelete = strpos($snapDesc, $checkStr)>-1?false:true; // to filter out those snapshots that are not form as part of AMI
     if($todelete) {
        if(in_array($snapshot->SnapshotId, $exclude_ids)) {
           continue;
        }
        $creationDate = $snapshot->StartTime;
        $currdate = date('Y-m-d');
        $duedate = date('Y-m-d', strtotime($creationDate." + $retention days"));
     	$currdate = date('Y-m-d', strtotime('2017-10-28')); // remove it from real code. Just for testing purpose
         
//	echo "SnapshotId: $snapshot->SnapshotId ; creation_date: $creationDate ;Curr: $currdate ; Due: $duedate";die;
	$currdateSeconds = strtotime($currdate);
        $duedateSeconds = strtotime($duedate);
        $secondsDiff = $currdateSeconds - $duedateSeconds;
        $days = $secondsDiff/86400;
        if($days < 0) {
           continue;
        }
        $params = array(
           "number" => 1,
           "region" => $region,
           "Snapshot_Id" => $snapshot->SnapshotId,
           "Description" => $description,
           "Instance_Name" => $resource_id,
           "accessKey" => $accessKey,
           "secretKey" => $secretKey
        );
        // lambda function to delete snapshots
        $result = $client->invoke(array(

            'FunctionName' => 'delete_snapshots',
            'InvocationType' => 'RequestResponse',
            'LogType' => 'Tail',
            'Payload' => json_encode($params)
        ));
        $data = json_decode($result->get("Payload"));
        print_r($data);echo "\n";
     }

  }
   
}
