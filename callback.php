<?php
declare(strict_types = 1);

if(isset($_POST["error"])) {
    header('Location: /error.html?msg=login');
    exit;
}

if(! (isset($_POST["code"]) && isset($_POST["state"])))
    die("params invalid");

$code = $_POST["code"];
$state = $_POST["state"];

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/dotenv-loader.php';

use \Auth0\SDK\API\Authentication;

$auth0_api = new Authentication(
    getenv('AUTH0_DOMAIN'),
    getenv('AUTH0_CLIENT_ID'),
    getenv('AUTH0_CLIENT_SECRET'), null, null,
    $guzzleOptions = ['proxy' => 'http://localhost:8888', 'verify' => false]
);

try {
    $result = $auth0_api->code_exchange($code, getenv('AUTH0_CALLBACK_URL'));
} catch (Exception $e) {
    die( $e->getMessage() );
}

$access_token = $result["access_token"];

if(!isset($access_token))
    die('error in exchange');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - Callback</title>
    <script src="http://cdn.auth0.com/js/auth0/9.12.2/auth0.min.js"></script>
</head>
<body>
<h1>Few more questions</h1>

<label for="given_name">Given name:</label>
<input type="text" id="given_name" name="given_name"><br><br>
<label for="family_name">Family name:</label>
<input type="text" id="family_name" name="family_name"><br><br>
<label for="password">Password:</label>
<input type="password" id="password" name="password"><br><br>

<input type="submit" value="Create Account" onclick="submit()">

<br/><a href="/">Start again</a>;

<script>
    const auth0js = new auth0.WebAuth({
        domain: "<?= getenv('AUTH0_DOMAIN') ?>",
        clientID: "<?= getenv('AUTH0_CLIENT_ID') ?>",
        redirectUri: "<?= getenv('SUCCESS_REDIRECT_URI') ?>",
        responseType: 'id_token'
    });

    function create_user(access_token, given_name, family_name, password) {
        let url = '/create-user.php';

        let data = {
            given_name: given_name,
            family_name: family_name,
            password: password
        };

        const params = {
            headers: {
                'content-type': 'application/json',
                'Authorization': 'Bearer ' + access_token
            },
            method: 'POST',
            body: JSON.stringify(data)
        };

        fetch(url, params)
            .then(data => data.json())
            .then(value => {
                auth0js.login(
                    {
                        'email' : value.email,
                        'password' : password,
                        'realm' : value.connection,
                    },
                    err => {
                        window.location.href = '/error.html?msg=' + err;
                    }
                );
            })
            .catch(err => console.log('error in create-user call: ' + err));
    }

    function submit() {
        let given_name = document.getElementById('given_name').value;
        let family_name = document.getElementById('family_name').value;
        let password = document.getElementById('password').value;
        create_user("<?= $access_token ?>", given_name, family_name, password);
    }

</script>
</body>
</html>



