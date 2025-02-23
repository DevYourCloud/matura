<?php

namespace App\Service;

use App\Entity\ConnectedDevice;
use Symfony\Component\String\ByteString;

class EncryptionService
{
    public const CRYPT_ALGO = 'aes-256-cbc';
    public const ENCRYPTED_TAG = '-ENCR';
    public const TOKEN_VALUE_SEPARATOR = '-';

    public const ACCESS_TOKEN_LENGTH = 32;

    public function __construct(
        private string $salt,
        private string $secretKey,
        private string $trustedDeviceVersion,
        private int $tokenLifetime,
    ) {
    }

    public function createConnectedDeviceHash(ConnectedDevice $connectedDevice): string
    {
        $data = implode([
            $connectedDevice->getServer()->getHost()->getDomain(),
            $connectedDevice->getIp(),
            $connectedDevice->getUserAgent(),
            $connectedDevice->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);

        return hash_hmac('sha256', $data, $this->salt);
    }

    public function createTrustedDeviceToken(ConnectedDevice $connectedDevice): string
    {
        if (null === $connectedDevice->getHash() || '' === $connectedDevice->getHash() || '0' === $connectedDevice->getHash()) {
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

    public function createAccessCode(): string
    {
        $code = '';
        for ($i = 0; $i < 5; ++$i) {
            $code .= rand(0, 9);
        }

        return $code;
    }

    public function getTokenExpirationDate(\DateTime $date): \DateTime
    {
        $date = clone $date;

        return $date->add(new \DateInterval('P'.$this->tokenLifetime.'D'));
    }

    private function getSecretKey(): string
    {
        return base64_decode($this->secretKey);
    }

    // @todo nick test it
    public function createAccessToken(): string
    {
        return ByteString::fromRandom(self::ACCESS_TOKEN_LENGTH)->toString();
    }
}
