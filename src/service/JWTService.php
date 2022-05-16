<?php

namespace App\Service;

use DateTimeImmutable;

class JWTService
{
    /**
     * Générate JWT
     *
     * @param array $header
     * @param array $payload
     * @param string $secret
     * @param integer $validity
     * @return string
     */
    public function generate(array $header, array $payload, string $secret, int $validity = 10800): string
    {
        if($validity > 0){
            $now = new DateTimeImmutable();
            $exp = $now->getTimestamp()+ $validity;
            
            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $exp;
        }

        // Save on base64
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));

        //Cleanning datas
        $base64Header = str_replace(['+','/','='], ['-','_',''], $base64Header);
        $base64Payload = str_replace(['+','/','='], ['-','_',''], $base64Payload);

        // Generate Sign
        $secret = base64_encode($secret);
        $signature = hash_hmac('sha256', $base64Header.'.'.$base64Payload, $secret, true);

        $base64Signature = base64_encode($signature);
        $base64Signature = str_replace(['+','/','='], ['-','_',''], $base64Signature);

        //Create Token
        $jwt = $base64Header.'.'.$base64Payload.'.'.$base64Signature;

        return $jwt;
    }

    /**
     * Check the token if is valid
     *
     * @param string $token
     * @return boolean
     */
    public function isValid(string $token): bool
    {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }

    /**
     * Get it the Header
     *
     * @param string $token
     * @return array
     */
    public function getHeaders(string $token): array
    {
        $array = explode('.', $token);
        $header = json_decode(base64_decode($array[0]), true);

        return $header;
    }

    /**
     * Get it the payload
     *
     * @param string $token
     * @return array
     */
    public function getPayload(string $token): array
    {
        $array = explode('.', $token);
        $payload = json_decode(base64_decode($array[1]), true);

        return $payload;
    }

    /**
     * Check if the token is Expired
     *
     * @param string $token
     * @return boolean
     */
    public function isExpired(string $token): bool
    {
        $payload = $this->getPayload($token);
        $now = new DateTimeImmutable();

        return $payload['exp'] < $now->getTimestamp();
    }

    public function check(string $token, string $secret)
    {
        //First get Header and Payload
        $header = $this->getHeaders($token);
        $payload = $this->getPayload($token);

        //Regenerate a token
        $verifToken = $this->generate($header, $payload, $secret, 0);
        
        return $token === $verifToken; 
    }
}