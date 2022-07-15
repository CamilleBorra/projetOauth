<?php

include 'conf.php';
include 'function.php';


function login()
{
    $queryParams=query(OAUTH_CLIENT_ID,'http://localhost:8081/callback','code','basic');
    echo "
        <form action='/callback' method='post'>
            <input type='text' name='username'/>
            <input type='password' name='password'/>
            <input type='submit' value='Login'/>
        </form>
    ";
    echo "<br><a href=\"http://localhost:8080/auth?{$queryParams}\">Login with OauthServer</a>";
    $queryParams=query(FACEBOOK_CLIENT_ID,'http://localhost:8081/fb_callback', 'code','public_profile,email');

    echo "<br><a href=\"https://www.facebook.com/v2.10/dialog/oauth?{$queryParams}\">Login with Facebook</a>";
    $queryParams=query(DISCORD_CLIENT_ID, 'http://localhost:8081/discord_callback','code', 'identify');

    // echo"<br><a href=\"https://discordapp.com/api/oauth2/authorize?{$queryParams}\">Login with Discord</a>";
    echo"<br><a href=\"https://discordapp.com/api/oauth2/authorize?{$queryParams}\">Login with Discord</a>";

   $queryParams=query(GIT_CLIENT_ID, 'http://localhost:8081/git_callback', 'code', 'public_profile,email', true);
    echo "<a href=\"https://github.com/login/oauth/authorize?{$queryParams}\">Login with GitHub</a>";

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


function discordcallback(){
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
    $context = stream_context_create(
        [
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

    echo "<br> <h1>Hello {$user['user']['username']}</h1>";
}

    function gitCallback()
    {
        ["code" => $code, "state" => $state] = $_GET;

        $specifParams = [
            'code' => $code,
            'state' => $state,
            'grant_type' => 'authorization_code',
        ];
        $queryParams = http_build_query(array_merge([
        'client_id' => GIT_CLIENT_ID,
        'client_secret' => GIT_CLIENT_SECRET,
        'redirect_uri' => 'http://localhost:8081/git_callback',
    ], $specifParams));

        $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/x-www-form-urlencoded\r\nAccept: application/json",
            'content' => $queryParams
        ]
    ]);


        $response = file_get_contents("https://github.com/login/oauth/access_token", false, $context);
        $token = json_decode($response, true);
 
        $context = stream_context_create([
        'http' => [
            'header' => "Authorization: token {$token['access_token']}\r\nUser-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.112 Safari/535.1"
            ]
        ]);
        $response = file_get_contents("https://api.github.com/user", false, $context);
        $user = json_decode($response, true);

        echo "Hello {$user['login']}";
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
    
    case '/git_callback':
        gitcallback();
        break;
    default:
        http_response_code(404);
        break;
}
