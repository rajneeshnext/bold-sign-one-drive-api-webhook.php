<?php
function getAccessToken($clientId, $clientSecret, $tenantId) {
    $url = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";
    $data = [
        "grant_type" => "client_credentials",
        "client_id" => $clientId,
        "client_secret" => $clientSecret,
        "scope" => "https://graph.microsoft.com/.default"
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    $fh = fopen(dirname(__FILE__).'/onedrive-access-token.txt','w');
    fwrite($fh, $response);
    fclose($fh);
    return $response ?? null;
}
function getUserId($accessToken){
    $ch = curl_init("https://graph.microsoft.com/v1.0/users");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $data = json_decode($response, true);
    $id = "";
    foreach($data['value'] as $users){
        //print_r($users);echo $users['id'];exit();
        if($users['mail'] == "@microsoft.."){//put ac email
            $id = $users['id'];
        }
    }
    //echo "<pre>";print_r($data);
    curl_close($ch);
    //echo "HTTP Code: " . $httpCode . "\n";
    //echo "Response: " . $response;
    return $id ?? null; // Returns drive ID
    
}
function getDriveId($accessToken, $userID) {
    //echo "<br/>";
    $url = "https://graph.microsoft.com/v1.0/users/$userID/drive"; // Replace {user-id} with actual user's ID
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ]);
    //echo "<pre>";
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    //print_r($data);
    return $data['id'] ?? null; // Returns drive ID
}

function uploadJsonToOneDrive($accessToken, $jsonFilePath, $fileName, $driveId) {
    echo "<br/><br/>";
    echo $url = "https://graph.microsoft.com/v1.0/drives/$driveId/root:/$jsonFilePath:/content";
    echo "<br/><br/>";
    
    $headers = [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, ""); // Create empty file
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 201 || $httpCode === 200) {
        echo "<br/><br/>File created successfully: $randomFileName\n";
    } else {
        echo "<br/><br/>Error creating file: $response\n";
        exit;
    }
    
    echo "<br/><br/>";
    echo $url = "https://graph.microsoft.com/v1.0/drives/{$driveId}/root:/{$fileName}:/content";
    echo "<br/><br/>";
    $jsonContent = file_get_contents($jsonFilePath);
    
    $dataArray = [
        "@microsoft.graph.conflictBehavior" => "replace",
        "name" => "John Doe",
        "email" => "johndoe@example.com",
        "role" => "Developer"
    ];
    
    //$jsonContent = json_encode($dataArray, JSON_PRETTY_PRINT);
    $fileSize = strlen($jsonContent);
    
    $headers = [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/octet-stream",  // Important: Treat it as binary data
        "Content-Length: $fileSize"
    ];

    if (!$jsonContent) {
        die("Error encoding JSON: " . json_last_error_msg());
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonContent);  // Send as binary
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Output response
    if ($httpCode == 201 || $httpCode == 200) {
        echo "<br/><br/>File updated successfully: " . $response;
    } else {
        echo "<br/><br/>Error creating file: " . $response;
    }
    curl_close($ch);
    return json_decode($response, true);
}
function getStoredToken(){
    $access_token = "";
    $fh = fopen(dirname(__FILE__).'/onedrive-access-token.txt','r');
    while ($line = fgets($fh)) {
      $data_r = $line;
      $data = json_decode($data_r, TRUE);
      $access_token = $data['access_token'];
    }
    fclose($fh);
    return $access_token;
}

$clientId = "xxxxx";
$clientSecret = "xxxxxd";
$tenantId = "xxxxxx";

$jsonFilePath = "jsonFiles/your_file.json"; // e.g., /var/www/data/sample.json
$fileName = "WebsiteAPI/sample.json"; // Name in OneDrive
$accessToken = getStoredToken();

if($accessToken == ""){
    echo "Empty access token<br/>";
    $accessToken = getAccessToken($clientId, $clientSecret, $tenantId);
}else{
    echo "Trying to upload<br/>";
    //echo "UserID: ";
    //echo $userID = getUserId($accessToken);
    //exit();
    //echo " <br/>DriveID:";
    //echo $driveId = getDriveId($accessToken, $userID);
    $driveId = "xxxxxx";
    //echo "<br/>";exit();
    
    $fileName = "data_" . bin2hex(random_bytes(5)) . ".json";
    $fileName = "WebsiteAPI/$fileName"; // Name in OneDrive
    
    $response = uploadJsonToOneDrive($accessToken, $jsonFilePath, $fileName, $driveId);
    echo "<pre>";
    //print_r($response);
    //exit();
    if(isset($response['error'])){
        echo "<br/>Generating new token <br/>";
        $accessToken = getAccessToken($clientId, $clientSecret, $tenantId);
    }else{
        
    }
}







