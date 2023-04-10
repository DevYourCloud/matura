<?php

namespace App\Service;

use App\Entity\ConnectedDevice;

class EncryptionService
{
    public const CRYPT_ALGO = 'aes-256-cbc';
    public const ENCRYPTED_TAG = '-ENCR';
    public const TOKEN_VALUE_SEPARATOR = '-';

    public function __construct(
        private string $salt,
        private string $secretKey,
        private string $trustedDeviceVersion,
        private string $tokenLifetime,
    ) {
    }

    public function createConnectedDeviceHash(ConnectedDevice $connectedDevice): string
    {
        $data = $connectedDevice->getServer()->getHost()->getDomain().$connectedDevice->getIp().$connectedDevice->getUserAgent();

        return hash_hmac('sha256', $data, $this->salt);
    }

    public function createTrustedDeviceToken(ConnectedDevice $connectedDevice): string
    {
        if (empty($connectedDevice->getHash())) {
            throw new \Exception(sprintf('Empty Hash for device on server %s', $connectedDevice->getServer()->getHost()->getDomain()));
        }

        $hash = $connectedDevice->getHash().self::TOKEN_VALUE_SEPARATOR.$this->trustedDeviceVersion;

        // Create a cipher of the appropriate length for this method.
        $ivSize = openssl_cipher_iv_length(self::CRYPT_ALGO);
        $iv = openssl_random_pseudo_bytes($ivSize);

        // Create the encryption.
        $encryptedData = openssl_encrypt(
            $hash,
            self::CRYPT_ALGO,
            $this->getSecretKey(),
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv.$encryptedData).self::ENCRYPTED_TAG;
    }

    public function decodeTrustedDeviceToken(string $token): string
    {
        // Removing #ENCR from token
        $token = substr($token, 0, -strlen(self::ENCRYPTED_TAG));
        $token = base64_decode($token);

        $ivSize = openssl_cipher_iv_length(self::CRYPT_ALGO);
        $iv = mb_substr($token, 0, $ivSize, '8bit');
        $realToken = mb_substr($token, $ivSize, null, '8bit');

        // Decrypting
        $decodedToken = openssl_decrypt(
            $realToken,
            self::CRYPT_ALGO,
            $this->getSecretKey(),
            OPENSSL_RAW_DATA,
            $iv
        );

        $decodedTokenValues = explode(self::TOKEN_VALUE_SEPARATOR, $decodedToken);
        $deviceHash = $decodedTokenValues[0] ?? null;
        $version = $decodedTokenValues[1] ?? null;

        if ($version !== $this->trustedDeviceVersion || null === $deviceHash) {
            throw new \Exception('Unable to decode token');
        }

        return $decodedTokenValues[0];
    }

    public function getTokenExpirationDate(): \DateTime
    {
        $now = new \DateTime('now');

        return $now->add(new \DateInterval('P'.$this->tokenLifetime.'D'));
    }

    private function getSecretKey(): string
    {
        return base64_decode($this->secretKey);
    }
}
