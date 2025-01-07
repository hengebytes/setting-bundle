<?php

namespace Hengebytes\SettingBundle\Service;

readonly class CryptoService
{
    public function __construct(
        private string $cryptoKey, // generated as `echo sodium_bin2hex(sodium_crypto_secretbox_keygen());`
    ) {
    }

    public function encrypt(string $message): string
    {
        $key = sodium_hex2bin($this->cryptoKey);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $cipher = base64_encode(
            $nonce .
            sodium_crypto_secretbox($message, $nonce, $key)
        );
        sodium_memzero($message);
        sodium_memzero($key);

        return $cipher;
    }

    /**
     * @throws \Exception|\SodiumException
     */
    public function decrypt(string $encrypted): string
    {
        $key = sodium_hex2bin($this->cryptoKey);
        $decoded = base64_decode($encrypted);
        if ($decoded === false) {
            throw new \Exception('Decrypt: Encoding failed');
        }
        if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            throw new \Exception('Decrypt: Message truncated');
        }
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        $plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        if ($plain === false) {
            throw new \Exception('Decrypt: Message tampered with in transit');
        }

        sodium_memzero($ciphertext);
        sodium_memzero($key);

        return $plain;
    }
}
