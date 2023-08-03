<?php

$target_dir = "uploads/audio/";
$target_file = $target_dir . basename($_FILES["audio"]["name"]);

echo $target_file;

if (move_uploaded_file($_FILES["audio"]["tmp_name"], $target_file)) {
    echo "The file ". basename( $_FILES["audio"]["name"]). " has been uploaded.";

    $openai_api_key = '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';
    
    $url = 'https://api.openai.com/v1/audio/transcriptions';
    
    $headers = [
        'Authorization: Bearer ' . $openai_api_key,
        'Content-Type: multipart/form-data'
    ];

  
    
    // Create a CURLFile object / preparing the file upload
    $cfile = new CURLFile($target_file, 'audio/mpeg', 'audio.mp3');  // replace with your actual file path
    
    // Assign POST data
    $data = [
        'file' => $cfile,
        'model' => 'whisper-1'
    ];


    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $response = curl_exec($ch);
    
    if (!$response) {
        die('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
    }
    
    $target_dir = "uploads/transcripts/";
    $target_file = $target_dir . basename($_FILES["audio"]["name"]);

    $responseArray = json_decode($response, true);  // convert JSON to PHP array
    $text = $responseArray['text']; 


    file_put_contents($target_file . '.txt', $text, FILE_APPEND);
    curl_close($ch);

    echo "Done transcribing";
    
} else {
    echo "Sorry, there was an error uploading your file.";
}
?>

