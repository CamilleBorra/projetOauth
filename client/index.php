<?php

define('OAUTH_CLIENT_ID', '621f59c71bc35');
define('OAUTH_CLIENT_SECRET', '621f59c71bc36');
define('FACEBOOK_CLIENT_ID', '1311135729390173');
define('FACEBOOK_CLIENT_SECRET', 'fc5e25661fe961ab85d130779357541e');
define('DISCORD_CLIENT_ID', '988809704195121173');
define('DISCORD_CLIENT_SECRET', 't_CBR3lemnUnrTfHjAgDJhotEgwQlCa_');


function login()
{
    $queryParams= http_build_query([
        'client_id' => OAUTH_CLIENT_ID,
        'redirect_uri' => 'http://localhost:8081/callback',
        'response_type' => 'code',
        'scope' => 'basic',
        "state" => bin2hex(random_bytes(16))
    ]);
    echo "
        <form action='/callback' method='post'>
            <input type='text' name='username'/>
            <input type='password' name='password'/>
            <input type='submit' value='Login'/>
        </form>
    ";
    echo "<br><a href=\"http://localhost:8080/auth?{$queryParams}\">Login with OauthServer</a>";
    $queryParams= http_build_query([
        'client_id' => FACEBOOK_CLIENT_ID,
        'redirect_uri' => 'http://localhost:8081/fb_callback',
        'response_type' => 'code',
        'scope' => 'public_profile,email',
        "state" => bin2hex(random_bytes(16))
    ]);
    echo "<br><a href=\"https://www.facebook.com/v2.10/dialog/oauth?{$queryParams}\">Login with Facebook</a>";

    $queryParams= http_build_query([
        'client_id' => DISCORD_CLIENT_ID,
        'redirect_uri' => 'http://localhost:8081/discord_callback',
        'response_type' => 'code',
        'scope' => 'identify',
        "state" => bin2hex(random_bytes(16))
    ]);
    // echo"<br><a href=\"https://discordapp.com/api/oauth2/authorize?{$queryParams}\">Login with Discord</a>";
    echo"<br><a href=\"https://discordapp.com/api/oauth2/authorize?{$queryParams}\">Login with Discord</a>";
}

// Exchange code for token then get user info
function callback()
{
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        ["username" => $username, "password" => $password] = $_POST;
        $specifParams = [
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password',
        ];
    } else {
        ["code" => $code, "state" => $state] = $_GET;

        $specifParams = [
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
    }

    $queryParams = http_build_query(array_merge([
        'client_id' => OAUTH_CLIENT_ID,
        'client_secret' => OAUTH_CLIENT_SECRET,
        'redirect_uri' => 'http://localhost:8081/callback',
    ], $specifParams));
    $response = file_get_contents("http://server:8080/token?{$queryParams}");
    $token = json_decode($response, true);
    
    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Bearer {$token['access_token']}"
            ]
        ]);
    $response = file_get_contents("http://server:8080/me", false, $context);
    $user = json_decode($response, true);
    echo "Hello {$user['lastname']} {$user['firstname']}";
}

function fbcallback()
{
    ["code" => $code, "state" => $state] = $_GET;

    $specifParams = [
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];

    $queryParams = http_build_query(array_merge([
        'client_id' => FACEBOOK_CLIENT_ID,
        'client_secret' => FACEBOOK_CLIENT_SECRET,
        'redirect_uri' => 'http://localhost:8081/fb_callback',
    ], $specifParams));
    $response = file_get_contents("https://graph.facebook.com/v2.10/oauth/access_token?{$queryParams}");
    $token = json_decode($response, true);
    
    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Bearer {$token['access_token']}"
            ]
        ]);
    $response = file_get_contents("https://graph.facebook.com/v2.10/me", false, $context);
    $user = json_decode($response, true);
    echo "Hello {$user['name']}";
}

function discordcallback()
{
    ["code" => $code, "state" => $state] = $_GET;
    $specifParams = [
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
    $queryParams = http_build_query(array_merge([
        'client_id' => DISCORD_CLIENT_ID,
        'client_secret' => DISCORD_CLIENT_SECRET,
        'redirect_uri' => 'http://localhost:8081/discord_callback',
        'response_type' => 'code',
        'scope' => 'identify',
        "state" => bin2hex(random_bytes(16))

    ], $specifParams));
    $context = stream_context_create([
        'http' => [
            'method' => "POST",
            'header' => "Content-type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: " . strlen($queryParams) . "\r\n",
            'content' => $queryParams
            ]
        ]
    );

    $response = file_get_contents("https://discordapp.com/api/oauth2/token", false, $context);
    $token = json_decode($response, true);
    $context = stream_context_create([
        'http' => [
            'method' => "GET",
            'header' => "Authorization: Bearer {$token['access_token']}"
            ]
        ]);
    $response = file_get_contents("https://discord.com/api/oauth2/@me", false, $context);
    $user = json_decode($response, true);
    echo "<pre>";
    print_r($user);
    echo"</pre>";
    echo "<br> <h1>Hello {$user['user']['username']}</h1>";
}

$route = $_SERVER["REQUEST_URI"];
switch (strtok($route, "?")) {
    case '/login':
        login();
        break;
    case '/callback':
        callback();
        break;
    case '/fb_callback':
        fbcallback();
        break;
    case '/discord_callback':
        discordcallback();
        break;
    default:
        http_response_code(404);
        break;
}
