<?php
date_default_timezone_set('Asia/Kolkata');
require_once __DIR__ . '/vendor/autoload.php';
// use Google\Cloud\Storage\StorageClient;

// include_once('HttpRequest.php'); 

use Google\Cloud\Storage\StorageClient;




define('APPLICATION_NAME', 'Drive API PHP Quickstart');
define('CREDENTIALS_PATH', '~/.credentials/drive-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
define('TEMP_STORAGE_PATH','/tmp/backup/');
define('BUCKET_NAME','gsuite-backup');
define('PROJECT_ID','tribes-ai');


// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/drive-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Drive::DRIVE,
  Google_Service_Drive::DRIVE_APPDATA,
  Google_Service_Drive::DRIVE_APPS_READ_ONLY,
  Google_Service_Drive::DRIVE_FILE,
  Google_Service_Drive::DRIVE_METADATA,
  Google_Service_Drive::DRIVE_METADATA_READONLY,
  Google_Service_Drive::DRIVE_PHOTOS_READONLY,
  Google_Service_Drive::DRIVE_READONLY,
  Google_Service_Drive::DRIVE_SCRIPTS,
  Google_Service_Storage::DEVSTORAGE_READ_WRITE,
  Google_Service_Storage::CLOUD_PLATFORM,
   Google_Service_Storage::DEVSTORAGE_FULL_CONTROL
  )
));

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfig(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, json_encode($accessToken));
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

function downloadSingleFIle($fileId,$fileName, $client){
    
     echo "======= Downloading =====".$fileName."===========".PHP_EOL;

    $fileCompletePath = TEMP_STORAGE_PATH.$fileName;

    $fileObj = fopen($fileCompletePath, "w");

    $service = new Google_Service_Drive($client);

    $optParams = array("supportsTeamDrives" => true,"alt" => "media");

    $results = $service->files->get($fileId,$optParams);

    $content = $results->getBody()->getContents();

    fwrite($fileObj, $content);

    fclose($fileObj);

    return $fileCompletePath;

    // echo json_encode($content);
}


// function downloadFile($service, $downloadUrl) {
//   // $downloadUrl = $file->getDownloadUrl();
//   if ($downloadUrl) {
//     $request = new Google_Http_Request($downloadUrl, 'GET', null, null);
//     $httpRequest = $service->getClient()->getAuth()->authenticatedRequest($request);
//     if ($httpRequest->getResponseHttpCode() == 200) {
//       echo json_encode($httpRequest->getResponseBody());
//     } else {
//       // An error occurred.
//       echo "Error occurred while downloadFile";
//     }
//   } else {
//     // The file doesn't have any content stored on Drive.
//     echo "FIle is empty";
//     return null;
//   }
// }

function getListofFiles(){
    // Get the API client and construct the service object.
    $client = getClient();
    $service = new Google_Service_Drive($client);

    // $downloadUrl = "https://doc-0o-18-docs.googleusercontent.com/docs/securesc/oqre6tq4mvr36vqat6mrlkmv9knnvfgd/4cd3hi17lf1d4sf8i8q5gpn8od26so14/1510394400000/09548092034393947527/18111447838682099008/0Byyv2heARTPcN0lzZkhsSEVzVVU?h=04009988862785900575&e=download&gd=true";

        // downloadFile($service, $downloadUrl);

        // die();

    // Print the names and IDs for up to 10 files.
    $optParams = array(
      'pageSize' => 1,
      'q' => "'1JPLIJwIkn7_fsAd9j99cbfmedqMxXw8u' in parents",
      // 'fields' => 'nextPageToken,files(id, name,mimeType)',
      'supportsTeamDrives' => true,
      includeTeamDriveItems => true,
      corpora => 'teamDrive',
      teamDriveId => '0ACpj9itzfml2Uk9PVA'
    );
    $results = $service->files->listFiles($optParams);
    // echo json_encode($results);die();
    // echo json_encode($results->getFiles());
    if (count($results->getFiles()) == 0) {
      print "No files found.\n";
    } else {
      print "Files:\n";
      foreach ($results->getFiles() as $file) {

        // echo json_encode($file);
        // echo $file->name.' => '.$file->id.'\n';

        // $fileObj = $service->files->get($file->id,array("supportsTeamDrives" => true))->getDownloadUrl();
        // echo $fileObj;die();

        // $downloadUrl = "https://doc-0o-18-docs.googleusercontent.com/docs/securesc/oqre6tq4mvr36vqat6mrlkmv9knnvfgd/4cd3hi17lf1d4sf8i8q5gpn8od26so14/1510394400000/09548092034393947527/18111447838682099008/0Byyv2heARTPcN0lzZkhsSEVzVVU?h=04009988862785900575&e=download&gd=true";

        // downloadFile($service, $downloadUrl);

        // downloadFile($service, $fileObj);
        $fileNameInCloudStorage = "";
        $donwloadedFilePath = downloadSingleFIle($file->id, $file->name, $client);

        upload_object($file->name,$donwloadedFilePath, $client);

        echo $file->name." Backed Up To Storage".PHP_EOL;

          // echo json_encode($file).'\n';
        // echo $file->name." -> ".$file->mimeType.' -> \n';
          // echo '\n========================\n';
      }
    }
}

function getTeamDrives(){
    $client = getClient();
    $service = new Google_Service_Drive($client);

      $optParams = array(
        // 'pageSize' => 10,
        // 'fields' => 'nextPageToken, files(id, name)',
        // 'supportsTeamDrives' => true,
        // includeTeamDriveItems => true,
        // corpora => 'teamDrive',
        // teamDriveId => 'Backup'
      );
    // echo json_encode($service);die();

    $results = $service->teamdrives->list;

    echo json_encode($results);

}


function teamDrivesByApi(){
 $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://www.googleapis.com/drive/v3/teamdrives?key=AIzaSyD-a9IF8KKYgoC3cpgS-Al7hLQDbugrDcw",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "authorization: Bearer ya29.GmAABXzJHypWlnZGHVWUwFjhfJgKukNPAQCKSIJTO0XlHuUao8qciD6-1Gs5ZeduRAYz7zWzhJ9I7LMdmdYYkn-2yFhvL8BH_S2EKFbM73uSA2S8Kh7Gjzu-wm73PXaAWnA",
    "cache-control: no-cache",
    "postman-token: 0a9d2a7e-0c13-174b-6ca0-a499e20082cd"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

echo json_encode($response);
}


getListofFiles();


function upload_object($objectName, $source, $client, $bucketName = BUCKET_NAME){
  $storage = new Google_Service_Storage($client);
  $obj = new Google_Service_Storage_StorageObject();
  $obj->setName($file_name);
  $storage->objects->insert(
    $bucketName,
    $obj,
    ['name' => "Vault/".$objectName, 'data' => file_get_contents($source), 'uploadType' => 'media']
);  
}
