<?php
$data = json_decode(file_get_contents('php://input'), true);

$given_name = htmlspecialchars($data["given_name"]);
$family_name = htmlspecialchars($data["family_name"]);
$password = htmlspecialchars($data["password"]);

if(! (isset($given_name) && isset($family_name) && isset($password))) {
    die('missing input');
}

function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

function getBearerToken() {
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

$access_token = getBearerToken();
if(! isset($access_token) )
    die('missing input');

header('Content-Type: application/json');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/dotenv-loader.php';

use \Auth0\SDK\API\Authentication;

$auth0_api = new Authentication(
    getenv('AUTH0_DOMAIN'),
    getenv('AUTH0_CLIENT_ID'),
    getenv('AUTH0_CLIENT_SECRET')
);

$pwl_user_info = $auth0_api->userinfo($access_token);

$email = $pwl_user_info['email'];
if(! isset($email)) {
    die('email missing');
}

$config = [
    'client_secret' => getenv('AUTH0_CLIENT_SECRET'),
    'client_id' => getenv('AUTH0_CLIENT_ID'),
    'audience' => getenv('AUTH0_MANAGEMENT_AUDIENCE'),
];

try {
    $result = $auth0_api->client_credentials($config);
} catch (Exception $e) {
    die( $e->getMessage() );
}

$access_token = $result["access_token"];

use Auth0\SDK\API\Management;

$mgmt_api = new Management( $access_token, getenv('AUTH0_DOMAIN') );

$username = rand(); // get username from backend database

$DB_CONNECTION_NAME = getenv('DB_CONNECTION_NAME');

$user_data = array(
    'connection' => $DB_CONNECTION_NAME,
    'given_name' => $given_name,
    'family_name' => $family_name,
    'email' => $email,
    'username' => "$username",
    'password' => $password,
    'email_verified'=> true,
    'verify_email' => false);

try {
    $create_result = $mgmt_api->users()->create($user_data);
} catch (Exception $e) {
    die( $e->getMessage() );
}

try {
    $delete_result = $mgmt_api->users()->delete($pwl_user_info['sub']);
} catch (Exception $e) {
    die( $e->getMessage() );
}


$result = array('success' => true, 'user_id' => $create_result['user_id'], 'email' => $email, 'connection' => $DB_CONNECTION_NAME );

echo json_encode($result, JSON_PRETTY_PRINT);
