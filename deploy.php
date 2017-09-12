<?php

// deploy - the target of the webhook

header("content-type: text/plain");

require 'deployment_key.php';

$git = "git";
$remote = "https://github.com/stegriff/paprika-grammar";
$destination_dir = ".";
$allow_get = false;

function sh($commands)
{
	return `2>&1 $commands`;
}

// Don't close this php block if you want to be able to use condenser.php

$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
$headers = apache_request_headers();
$supplied_key = null;

//Decide how we're going to get the secret deployment key
if ($is_post && isset($headers['X-Hub-Signature']))
{
	/* [Option 1] Handle a POST with X-Hub-Signature */
	// Looks like "sha1=f83e18d43dde7249a5980f44bf89c5735273fe06"
	$supplied_key = $headers['X-Hub-Signature'];
	$parts = explode('=', $supplied_key);
	$algo = $parts[0]; // Like 'sha1'
	$supplied_key = $parts[1]; // Like 'f83e18d43dde7249a5980f44bf89c5735273fe06'
	
	$post_body = file_get_contents('php://input');
	
	//Hash the received POST with the real key
	// the hash should match the X-Hub-Sig
	$deployment_key = hash_hmac($algo, $post_body, $deployment_key);
	echo "Hash at server  : $deployment_key \r\n";
	echo "Hash from client: $supplied_key \r\n";
}
else if ($allow_get === true)
{
	/* [Option 2] Handle a GET with key, for clients that don't support X-Hub-Signature */
	$supplied_key = $_GET['key'];
}
else
{
	echo "No X-Hub-Signature was received in POST request, and GET is disabled. Deployment impossible!";
}

if ($supplied_key == $deployment_key)
{
	$output = sh("$git pull");
	echo $output;
}
else
{
	echo "Deployment key incorrect ($supplied_key)";
}
?>