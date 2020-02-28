<?php
declare(strict_types = 1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/dotenv-loader.php';

if(! (isset($_POST["email"]) && isset($_POST["token"])))
    die("params invalid");

$email = $_POST["email"];
$gRecaptchaResponse = htmlspecialchars($_POST["token"]);

$recaptcha = new \ReCaptcha\ReCaptcha(getenv('RECAPTCHA_SECRET'));
$resp = $recaptcha->setExpectedHostname(getenv('RECAPTCHA_SITE_NAME'))
                  ->verify($gRecaptchaResponse, $_SERVER['REMOTE_ADDR']);

if (! $resp->isSuccess()) {
    $errors = $resp->getErrorCodes();
    header('Location: /error.html?msg=captch-failed');
    exit;
}

use \Auth0\SDK\API\Authentication;
use Auth0\SDK\Store\SessionStore;
use Auth0\SDK\Store\CookieStore;
use Auth0\SDK\Helpers\TransientStoreHandler;
use Auth0\SDK\Auth0;

$auth0_api = new Authentication(
    getenv('AUTH0_DOMAIN'),
    getenv('AUTH0_CLIENT_ID'),
    getenv('AUTH0_CLIENT_SECRET'), null, null,
    //$guzzleOptions = ['proxy' => 'http://localhost:8888', 'verify' => false]
);

$config = [
    'audience' => getenv('AUTH0_MANAGEMENT_AUDIENCE'),
];

try {
    $result = $auth0_api->client_credentials($config);
} catch (Exception $e) {
    die( $e->getMessage() );
}

$access_token = $result["access_token"];

use Auth0\SDK\API\Management;

$mgmt_api = new Management( $access_token, getenv('AUTH0_DOMAIN'));
$connection_name = getenv('DB_CONNECTION_NAME');

$search_params = array(
    'q' => urlencode("identities.connection:\"$connection_name\" AND email:\"$email\""),
    'search_engine' => 'v3',
    'fields' => ['user_id', 'email']
);

try {
    $search_result = $mgmt_api->users()->getAll($search_params);
} catch (Exception $e) {
    die( $e->getMessage() );
}

if(!empty($search_result)) {
    header('Location: /error.html?msg=email-taken');
    exit;
}

// Generate and store a state value.
$transient_store = new CookieStore();
$state_handler = new TransientStoreHandler($transient_store);
$state_value = $state_handler->issue(Auth0::TRANSIENT_STATE_KEY);

$authParams = array(
    'redirect_uri' => getenv('AUTH0_CALLBACK_URL'),
    'response_type' => 'token',
    'scope' => 'openid email',
    //'state' => $state_value
);



// TODO: check no matching user
$auth0_api->email_passwordless_start($email, 'link', $authParams);

echo "Thank you. Please check your mailbox.<br/>";
echo '<a href="/">Try again</a>';

