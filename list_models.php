<?php
$apiKey = 'AIzaSyCIG0-yZLHCmDEJXErEdUc-vYZ_0SyQuZ4';
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Response: " . $res . "\n";
