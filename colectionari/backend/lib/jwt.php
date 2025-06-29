<?php
const JWT_SECRET = 'super_secret_key_change_me';

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function generateJWT($payload, $secret = JWT_SECRET) {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $header_encoded = base64UrlEncode(json_encode($header));
    $payload_encoded = base64UrlEncode(json_encode($payload));
    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
    $signature_encoded = base64UrlEncode($signature);
    return "$header_encoded.$payload_encoded.$signature_encoded";
}

function verifyJWT($token, $secret = JWT_SECRET) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;

    list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
    $signature_check = base64UrlEncode(hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true));

    if (!hash_equals($signature_check, $signature_encoded)) return false;

    $payload = json_decode(base64UrlDecode($payload_encoded), true);
    if (isset($payload['exp']) && $payload['exp'] < time()) return false;

    return $payload;
} 