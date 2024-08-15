<?php
function curl_get($url) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}
function curl_post($url, $data) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}
if (!isset($_GET['language'])) {
	http_response_code(400);
	header('Content-Type: text/plain');
	echo "No language specified!";
	exit();
}
if (!isset($_GET['code'])) {
	http_response_code(400);
	header('Content-Type: text/plain');
	echo "No code specified!";
	exit();
}
$runtimes = json_decode(curl_get('https://emkc.org/api/v2/piston/runtimes'), true);
$values = [
	'language' => $_GET['language'],
	'version' => 'N/A',
	'code' => $_GET['code']
];
$foundRuntime = false;
foreach ($runtimes as $runtime) {
	$runtimeNames = [...[$runtime['language']], ...$runtime['aliases']];
	foreach ($runtimeNames as $runtimeName) {
		if (strtolower($runtimeName) == strtolower($_GET['language'])) {
			$values['version'] = $runtime['version'];
			$foundRuntime = true;
		}
	}
}
if (!$foundRuntime) {
	http_response_code(404);
	header('Content-Type: text/plain');
	echo "Runtime not found!";
	exit();
}
$payload = [
	'language' => $values['language'],
	'version' => $values['version'],
	'files' => [[
		'content' => $values['code']
	]]
];
header('Content-Type: text/plain');
$executeResult = json_decode(curl_post('https://emkc.org/api/v2/piston/execute', json_encode($payload)), true);
$response = '';
if (isset($executeResult['compile'])) {
	if ($executeResult['compile']['code'] != 0) {
		if (mb_strlen($response) <= 1) {
			$response = 'Compile-Output: (Code ' . $executeResult['compile']['code'] . '): ' . $executeResult['compile']['output'];
		} else {
			$response .= '; Compile-Output: (Code ' . $executeResult['compile']['code'] . '): ' . $executeResult['compile']['output'];
		}
	}
}
if (isset($executeResult['run'])) {
	if (mb_strlen($response) <= 1) {
		$response = 'Run-Output: (Code ' . $executeResult['run']['code'] . '): ' . $executeResult['run']['output'];
	} else {
		$response .= '; Run-Output: (Code ' . $executeResult['run']['code'] . '): ' . $executeResult['run']['output'];
	}
}
if (mb_strlen($response) > 499) {
	$response = substr($response, 0, 499);
}
echo trim($response);
?>
