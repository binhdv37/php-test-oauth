<!DOCTYPE html>
<html>
<body>

<?php

function apiRequest($url, $post=FALSE, $headers=array()) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  if($post)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

  $headers = [
    'Accept: application/vnd.github.v3+json, application/json',
    'User-Agent: http://localhost:4200'
  ];

  if(isset($_SESSION['access_token']))
    $headers[] = 'Authorization: Bearer '.$_SESSION['access_token'];

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $response = curl_exec($ch);
  return json_decode($response, true);
}

// Fill these out with the values from GitHub
$githubClientID = 'd68c41dae3d9e4b61501';
$githubClientSecret = '6361eae0773ad05d46b193a82d022fc1b308e80e';

// This is the URL we'll send the user to first
// to get their authorization
$authorizeURL = 'https://github.com/login/oauth/authorize';

// This is the endpoint we'll request an access token from
$tokenURL = 'https://github.com/login/oauth/access_token';

// This is the GitHub base URL for API requests
$apiURLBase = 'https://api.github.com/';

// The URL for this script, used as the redirect URL
//$baseURL = 'http://' . $_SERVER['SERVER_NAME']
//    . $_SERVER['PHP_SELF'];
$baseURL = 'http://localhost:4200';

// Start a session so we have a place to
// store things between redirects
session_start();

// If there is an access token in the session
// the user is already logged in
if(!isset($_GET['action'])) {
  if(!empty($_SESSION['access_token'])) {
    echo '<h3>Logged In</h3>';
    echo '<p><a href="?action=repos">View Repos</a></p>';
    echo '<p><a href="?action=logout">Log Out</a></p>';
  } else {
    echo '<h3>Not logged in</h3>';
    echo '<p><a href="?action=login">Log In</a></p>';
  }
  die();
}

// Start the login process by sending the user
// to GitHub's authorization page
if(isset($_GET['action']) && $_GET['action'] == 'login') {
    unset($_SESSION['access_token']);

    // Generate a random hash and store in the session
    $_SESSION['state'] = bin2hex(random_bytes(16));

    $params = array(
        'response_type' => 'code',
        'client_id' => $githubClientID,
        'redirect_uri' => $baseURL,
        'scope' => 'user public_repo',
        'state' => $_SESSION['state']
    );

    // Redirect the user to GitHub's authorization page
    header('Location: '.$authorizeURL.'?'.http_build_query($params));
    die();
}

// When GitHub redirects the user back here,
// there will be a "code" and "state" parameter in the query string
if(isset($_GET['code'])) {
    // Verify the state matches our stored state
    if(!isset($_GET['state'])
        || $_SESSION['state'] != $_GET['state']) {

        header('Location: ' . $baseURL . '?error=invalid_state');
        die();
    }

    // Exchange the auth code for an access token
    $token = apiRequest($tokenURL, array(
        'grant_type' => 'authorization_code',
        'client_id' => $githubClientID,
        'client_secret' => $githubClientSecret,
        'redirect_uri' => $baseURL,
        'code' => $_GET['code']
    ));
    $_SESSION['access_token'] = $token['access_token'];

    header('Location: ' . $baseURL);
    die();
}

?>

</body>
</html>