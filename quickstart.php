<?php
date_default_timezone_set('Asia/Kolkata');
require_once __DIR__ . '/vendor/autoload.php';
// use Google\Cloud\Storage\StorageClient;

// include_once('HttpRequest.php'); 

use Google\Cloud\Storage\StorageClient;


$existed = in_array("gs", stream_get_wrappers());
if ($existed) {
    stream_wrapper_unregister("gs");
}
stream_wrapper_register("gs", "Google\Cloud\Storage\StreamWrapper");



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

     echo "======= Got api result for =====".$fileName."===========".PHP_EOL;


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
      'fields' => 'nextPageToken,files(id, name,mimeType,size)',
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
        echo "File Name :".$file->name." File Size ".(int)$file->size." DownloadUrl".$file->downloadUrl.PHP_EOL;

        
        downloadInChunksAndStorInFile($file);
        // echo json_encode($file); 
        // echo $file->name.' => '.$file->id.'\n';

        // $fileObj = $service->files->get($file->id,array("supportsTeamDrives" => true))->getDownloadUrl();
        // echo $fileObj;die();

        // $downloadUrl = "https://doc-0o-18-docs.googleusercontent.com/docs/securesc/oqre6tq4mvr36vqat6mrlkmv9knnvfgd/4cd3hi17lf1d4sf8i8q5gpn8od26so14/1510394400000/09548092034393947527/18111447838682099008/0Byyv2heARTPcN0lzZkhsSEVzVVU?h=04009988862785900575&e=download&gd=true";

        // downloadFile($service, $downloadUrl);

        // downloadFile($service, $fileObj);
        $fileNameInCloudStorage = "";
        // $donwloadedFilePath = downloadSingleFIle($file->id, $file->name, $client);

        // upload_object($file->name,$donwloadedFilePath, $client);

        // echo $file->name." Backed Up To Storage".PHP_EOL;

          // echo json_encode($file).'\n';
        // echo $file->name." -> ".$file->mimeType.' -> \n';
          // echo '\n========================\n';
      }
    }
}

function downloadInChunksAndStorInFile($fileObj){
        echo "Starting downloading for ".$fileName.PHP_EOL;

        $fileId = $fileObj->id;
        $fileName = $fileObj->name;
        $fileSize = $fileObj->size;
        
        $client = getClient();

        // Get the authorized Guzzle HTTP client
        $http = $client->authorize();

        $fileCompletePath = TEMP_STORAGE_PATH.'/'.$fileName;

        $fp = fopen($fileCompletePath, 'w');

        echo "Cloud Storage file path gs://".BUCKET_NAME.'/Vault/'.$fileName.PHP_EOL;

      
        
        // $context = stream_context_create();

        // $cloudStorageFilerHandler = fopen("gs://".BUCKET_NAME.'/Vault/'.$fileName, 'w');


        // Download in 10 MB chunks
        $chunkSizeBytes = 10 * 1024 * 1024;
        $chunkStart = 0;


        // Iterate over each chunk and write it to our file
        while ($chunkStart < $fileSize) {

            $chunkEnd = $chunkStart + $chunkSizeBytes;

            echo "For file ".$fileName." Downloading from ".$chunkStart." to ".$chunkEnd.PHP_EOL;


            $response = $http->request(
            'GET',
            sprintf('/drive/v3/files/%s', $fileId),
            [
            'query' => ['alt' => 'media'],
            'headers' => [
            'Range' => sprintf('bytes=%s-%s', $chunkStart, $chunkEnd)
            ]
            ]
            );
            $chunkStart = $chunkEnd + 1;
            fwrite($fp, $response->getBody()->getContents());
      }
      // close the file pointer
      fclose($fp);

      upload_object($fileName,$fileCompletePath, $client);      

      // uploadChunkToCloudStorage($cloudStorageFilerHandler,$response->getBody()->getContents());

      // fclose($cloudStorageFilerHandler);
}

getListofFiles();


function uploadChunkToCloudStorage($fp, $content){

  // move_uploaded_file('/tmp/backup/test.txt', "gs://gsuite-backup/Vault/test.txt");
  fwrite($fp, $content);

  // echo "Uploaded :".$filePath.PHP_EOL;
}


function upload_object($objectName, $source, $client, $bucketName = BUCKET_NAME){
  $storage = new Google_Service_Storage($client);
  $obj = new Google_Service_Storage_StorageObject();
  $obj->setName($file_name);
  $storage->objects->insert(
    $bucketName,
    $obj,
    ['name' => "Vault/".$objectName, 'data' => file_get_contents($source), 'uploadType' => 'media']

);  

  echo $objectName." was uploaded to storage ".PHP_EOL;
  //Delete local temp copy after backing it up
  // unlink($soßurce);
}
