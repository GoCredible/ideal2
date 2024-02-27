<?php

namespace PetervdBroek\iDEAL2\Utils;

use OpenSSLCertificate;
use OpenSSLAsymmetricKey;
use PetervdBroek\iDEAL2\Exceptions\InvalidDigestException;
use PetervdBroek\iDEAL2\Exceptions\InvalidSignatureException;

class Signer
{
    private OpenSSLCertificate $certificate;
    private OpenSSLAsymmetricKey $privateKey;
    private OpenSSLCertificate $acquirer_certificate;

    /**
     * @param $certificateFilePath
     * @param $privateKeyFilePath
     */
    public function __construct($certificateFilePath, $privateKeyFilePath, $acquirerCertificateFilePath)
    {
        $this->certificate = openssl_x509_read(file_get_contents($certificateFilePath));
        $this->privateKey = openssl_get_privatekey(file_get_contents($privateKeyFilePath));
        $this->acquirer_certificate = openssl_x509_read(file_get_contents($acquirerCertificateFilePath));
    }

    /**
     * @param string $body
     * @return string
     */
    public static function getDigest(string $body): string
    {
        return "SHA-256=" . base64_encode(hash('sha256', $body, true));
    }

    /**
     * @param array $headers
     * @return string
     */
    public function getSignature(array $headers): string
    {
        $signString = $this->getSignString($headers);
        $headersToSign = strtolower(implode(' ', array_keys($headers)));

        return sprintf(
            'keyId="%s", algorithm="SHA256withRSA", headers="%s", signature="%s"',
            $this->getFingerprint(),
            $headersToSign,
            $this->getSignedString($signString)
        );
    }

    /**
     * @param array $headers
     * @param string $body
     * @throws InvalidDigestException
     */
    public function verifyResponse(array $headers, string $body): void
    {
        $this->verifyDigest($headers, $body);
        $this->verifySignature($headers);
    }

    /**
     * @param array $headers
     * @return string
     */
    private function getSignString(array $headers): string
    {
        $signString = "";
        foreach ($headers as $key => $header) {
            $signString .= sprintf("%s: %s\n", strtolower($key), trim($header));
        }

        return trim($signString);
    }

    /**
     * @return string
     */
    private function getFingerprint(): string
    {
        return openssl_x509_fingerprint($this->certificate);
    }

    /**
     * @param $signString
     * @return string
     */
    private function getSignedString($signString): string
    {
        $binary = "";
        openssl_sign($signString, $binary, $this->privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($binary);
    }

    /**
     * @param array $headers
     * @param string $body
     * @return void
     * @throws InvalidDigestException
     */
    private function verifyDigest(array $headers, string $body): void
    {
        if ($headers['Digest'][0] !== self::getDigest($body)) {
            throw new InvalidDigestException();
        }
    }

    /**
     * @param array $headers
     * @return void
     */
    private function verifySignature(array $headers): void
    {
        $signature_arr = $this->getSignatureArr($headers['Signature'][0]);
        $finger_print = openssl_x509_fingerprint($this->acquirer_certificate);
        if ($finger_print != strtolower( $signature_arr['keyId'])) {
            throw new InvalidSignatureException();
        }
        
        $header_keys_to_sign = explode(" ", $signature_arr['headers']);
        $headers_to_sign = [];
        $headers = array_change_key_case($headers, CASE_LOWER );
        foreach($header_keys_to_sign as $k){
            if(!empty($headers[$k][0])){
                $headers_to_sign[$k] = $headers[$k][0];
            }
        }
        $signString = $this->getSignString($headers_to_sign);

        if (!openssl_verify($signString, base64_decode($signature_arr['signature']), $this->acquirer_certificate, OPENSSL_ALGO_SHA256)) {
            throw new InvalidSignatureException();
        }
    }

    private function getSignatureArr(string $signature): array
    {
        $result = [];
        
        foreach(explode(",", $signature) as $str) {

            preg_match_all('/(\w+)\s*=\s*("[^"]*"|\'[^\']*\')/', $str, $match, PREG_SET_ORDER);
            $result[ $match[0][1] ] = str_replace('"', '', $match[0][2]);
            
        }
        return $result;
    }
}
