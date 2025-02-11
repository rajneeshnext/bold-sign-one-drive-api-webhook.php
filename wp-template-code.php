<?php
    /* Template Name: Webhook Bold Sign */
function getStoredToken(){
    $access_token = "";
	//echo dirname(__FILE__).'/onedrive-access-token.txt';
    $fh = fopen(dirname(__FILE__).'/onedrive-access-token.txt','r');
    while ($line = fgets($fh)) {
      $data_r = $line;
      $data = json_decode($data_r, TRUE);
      $access_token = $data['access_token'];
    }
    fclose($fh);
    return $access_token;
}
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
function uploadJsonToOneDrive($accessToken, $jsonFilePath, $fileName, $driveId, $reset=0) {
    //echo $accessToken;
    echo "<br/><br/>Reset: ".$reset."<br/>";
    $url = "https://graph.microsoft.com/v1.0/drives/$driveId/root:/$fileName:/content";
    //echo "<br/><br/>";
    
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
        echo "<br/><br/>Blank File created successfully: $randomFileName\n";
    } else {
        echo "<br/><br/>Error creating Blank file: $response\n";
        $response = json_decode($response, true);
        //echo "<pre>";print_r($response);
        if(isset($response['error']['code']) && $response['error']['code'] == "InvalidAuthenticationToken"){  
            $clientId = "xxxxx";
    	    $clientSecret = "xxxxxx";
	    //echo "tenantId";
    	    $tenantId = "xxxxx";
            $newAccessTokenJson = getAccessToken($clientId, $clientSecret, $tenantId);
            if ($newAccessTokenJson && $reset==0) {
				echo "<br/>Updating new<br/>";
				$reset=1;
				$newAccessTokenArray = json_decode($newAccessTokenJson, TRUE);
      			$newAccessToken = $newAccessTokenArray['access_token'];
				return uploadJsonToOneDrive($newAccessToken, $jsonFilePath, $fileName, $driveId, $reset); // Fixed: Return the function call
			} else {
				die("Failed to obtain a new access token.");
			}
        }
        exit;
    }
    
    echo "<br/><br/>";
    $url = "https://graph.microsoft.com/v1.0/drives/{$driveId}/root:/{$fileName}:/content";
    //echo "<br/><br/>";
    $jsonContent = $jsonFilePath;
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
        echo "<br/><br/>File updated successfully: ";
    } else {
        echo "<br/><br/>Error creating file: " . $response;
        if(isset($response['error']['code']) && $response['error']['code'] == "InvalidAuthenticationToken"){  
            $clientId = "xxxxx";
    		$clientSecret = "xxxxx";
    	   $tenantId = "xxxxx";
            $newAccessToken = getAccessToken($clientId, $clientSecret, $tenantId);
            if ($newAccessToken && $reset==0) {
				$reset=1;
				return uploadJsonToOneDrive($newAccessToken, $jsonFilePath, $fileName, $driveId, $reset); // Fixed: Return the function call
			} else {
				die("Failed to obtain a new access token.");
			}
        }
    }
    curl_close($ch);
    return json_decode($response, true);
}
    $payload = file_get_contents("php://input");
    // Convert JSON to an array
    // Log the webhook payload (optional for debugging)
    //file_put_contents("webhook-log.txt", $payload);
	/*$payload ='{"event":{"id":"26c7e5df-1ffa-4109-b9a0-93984650114f","created":1738829289,"eventType":"Completed","clientId":null,"environment":"Test"},"data":{"object":"document","documentId":"d0cc1843-dd1a-4536-ab63-19dee827facd","messageTitle":"Agreement for Raj Saini","documentDescription":"Please review and sign the document.","status":"Completed","senderDetail":{"name":"Tesfu Hailu","emailAddress":"newpatient@stellarpathology.com"},"signerDetails":[{"signerName":"Raj Saini","signerRole":"Customer","signerEmail":"tesfu2@gmail.com","phoneNumber":null,"status":"Completed","enableAccessCode":false,"isAuthenticationFailed":null,"enableEmailOTP":false,"isDeliveryFailed":false,"isViewed":true,"order":1,"signerType":"Signer","isReassigned":false,"reassignMessage":null,"declineMessage":null,"lastActivityDate":1738829286,"authenticationType":"None","idVerification":null,"allowFieldConfiguration":false,"lastReminderSentOn":null,"authenticationRetryCount":null}],"ccDetails":[],"onBehalfOf":null,"createdDate":1738828979,"expiryDate":1744088399,"enableSigningOrder":false,"disableEmails":false,"revokeMessage":null,"errorMessage":null,"labels":[],"isCombinedAudit":false,"BrandId":"52f3d2a5-27ab-4d23-b189-84d5af7cd818","documentDownloadOption":"Combined","metaData":{}},"document":{"object":"document","documentId":"d0cc1843-dd1a-4536-ab63-19dee827facd","messageTitle":"Agreement for Raj Saini","documentDescription":"Please review and sign the document.","status":"Completed","senderDetail":{"name":"Tesfu Hailu","emailAddress":"newpatient@stellarpathology.com"},"signerDetails":[{"signerName":"Raj Saini","signerRole":"Customer","signerEmail":"tesfu2@gmail.com","phoneNumber":null,"status":"Completed","enableAccessCode":false,"isAuthenticationFailed":null,"enableEmailOTP":false,"isDeliveryFailed":false,"isViewed":true,"order":1,"signerType":"Signer","isReassigned":false,"reassignMessage":null,"declineMessage":null,"lastActivityDate":1738829286,"authenticationType":"None","idVerification":null,"allowFieldConfiguration":false,"lastReminderSentOn":null,"authenticationRetryCount":null}],"ccDetails":[],"onBehalfOf":null,"createdDate":1738828979,"expiryDate":1744088399,"enableSigningOrder":false,"disableEmails":false,"revokeMessage":null,"errorMessage":null,"labels":[],"isCombinedAudit":false,"BrandId":"52f3d2a5-27ab-4d23-b189-84d5af7cd818","documentDownloadOption":"Combined","metaData":{}}}';*/
	
	$data = json_decode($payload, true);
	// Convert JSON to an array
    //echo "<pre>";print_r($data['data']);
    
    $documentId = $data['data']['documentId']; 
    $api_key = "xxxxxx";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.boldsign.com/v1/document/properties?documentId=".$documentId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-KEY: ' . $api_key,
        ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data_all = json_decode($response, true);
    foreach($data_all['signerDetails'][0]['formFields'] as $formField){
        if($formField['id'] == "TextBox12"){
            $data['data']['representative_relation'] = $formField['value'];
        }
        if($formField['id'] == "TextBox11"){
            $data['data']['representative_name'] = $formField['value'];
        }
        if($formField['id'] == "EditableDate2"){
            $data['data']['EditableDate2'] = $formField['value'];
        }
        if($formField['id'] == "TextBox9"){
            $data['data']['name'] = $formField['value'];
        }
        if($formField['id'] == "TextBox13"){
            $data['data']['representative_phone'] = $formField['value'];
        }
        if($formField['id'] == "EditableDate1"){
            $data['data']['EditableDate1'] = $formField['value'];
        }
        if($formField['id'] == "TextBox10"){
            $data['data']['place_surgery_performed'] = $formField['value'];
        }
        if($formField['id'] == "TextBox1"){
            $data['data']['address'] = $formField['value'];
        }
        if($formField['id'] == "TextBox2"){
            $data['data']['suite'] = $formField['value'];
        }
        if($formField['id'] == "TextBox3"){
            $data['data']['city'] = $formField['value'];
        }
        if($formField['id'] == "TextBox4"){
            $data['data']['state'] = $formField['value'];
        }
        if($formField['id'] == "TextBox5"){
            $data['data']['zipcode'] = $formField['value'];
        }
        if($formField['id'] == "TextBox6"){
            $data['data']['country'] = $formField['value'];
        }
        if($formField['id'] == "TextBox7"){
            $data['data']['phone'] = $formField['value'];
        }
        if($formField['id'] == "TextBox8"){
            $data['data']['email'] = $formField['value'];
        }
		if($formField['id'] == "TextBox14"){
            $data['data']['dob'] = $formField['value'];
        }
    }
    //echo "<pre>";print_r($data);exit();
    
    // Check if the event is "documentSigned"
    if (isset($data['data']) && $data['data']['status'] === "Completed") {
		$documentId = $data['data']['documentId'];
		//file_put_contents("formdata-$documentId.json", json_encode($data['data']));
		echo json_encode(["status" => "success", "message" => "Webhook processed successfully."]);
		
		$clientId = "xxxx";
		$clientSecret = "xxxxxxx";
		$tenantId = "xxxxxx";

		$jsonFilePath = json_encode($data['data']); // e.g., /var/www/data/sample.json
		$accessToken = getStoredToken();
		//echo "<pre>";print_r($data['data']);exit();
		if($accessToken == ""){
			echo "Empty access token<br/>";
			$accessToken = getAccessToken($clientId, $clientSecret, $tenantId);
		}else{
			echo "<br/>Trying to upload<br/>";
			//echo "UserID: ";
			//echo $userID = getUserId($accessToken);
			//exit();
			//echo " <br/>DriveID:";
			//echo $driveId = getDriveId($accessToken, $userID);
			$driveId = "b!wJ5RazNiHkWH3d1r4H3IsVloFB6PfJ5JhBmDim9AiHmqwohMEUd2TbCXuKee-cSK";
			//echo "<br/>";exit();
			//echo $driveId = "b!-RIj2DuyvEyV1T4NlOaMHk8XkS_I8MdFlUCq1BlcjgmhRfAj3-Z8RY2VpuvV_tpd";

			$fileName = "data_" . bin2hex(random_bytes(5)) . ".json";
			$fileName = "newcase/$fileName"; // Name in OneDrive

			$response = uploadJsonToOneDrive($accessToken, $jsonFilePath, $fileName, $driveId);
			//echo "<pre>";
			//print_r($response);
			//exit();
			if(isset($response['error'])){
				echo "<br/>Generating new token <br/>";
				$accessToken = getAccessToken($clientId, $clientSecret, $tenantId);
			}else{

			}
		}
	} else {
        echo json_encode(["status" => "ignored", "message" => "Not a documentSigned event."]);
    }

?>
