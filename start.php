<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/dotenv-loader.php';

$email = $_POST["email"];
$gRecaptchaResponse = $_POST["token"];

if(! (isset($email)))
    die("params invalid");

$recaptcha = new \ReCaptcha\ReCaptcha(getenv('RECAPTCHA_SECRET'));
$resp = $recaptcha->setExpectedHostname(getenv('RECAPTCHA_SITE_NAME'))
                  ->verify($gRecaptchaResponse, $_SERVER['REMOTE_ADDR']);

if ($resp->isSuccess()) {
    // Verified!
} else {
    $errors = $resp->getErrorCodes();
    die('recaptcha failed! ' + $errors);
}

use \Auth0\SDK\API\Authentication;
use Auth0\SDK\Store\SessionStore;
use Auth0\SDK\Store\CookieStore;
use Auth0\SDK\Helpers\TransientStoreHandler;
use Auth0\SDK\Auth0;

$auth0_api = new Authentication(
    getenv('AUTH0_DOMAIN'),
    getenv('AUTH0_CLIENT_ID'),
    getenv('AUTH0_CLIENT_SECRET')
);

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

echo "Please check your mailbox at <code>$email</code><br/>";
echo '<a href="/">Try again</a>';

