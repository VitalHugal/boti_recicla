<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Encrypt extends Model
{
    private string $method = 'aes-256-cbc';
    private int $iv_size = 16;
    private string $key;

    public function __construct($key = '#4eergeh345983746bs')
    {
        $this->key = $key;
    }

    /**
     * @param $message
     * @return string
     */
    function encrypt($message): string
    {
        $iv = openssl_random_pseudo_bytes($this->iv_size);
        $ciphertext = openssl_encrypt($message, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
        $ciphertext_hex = bin2hex($ciphertext);
        $iv_hex = bin2hex($iv);
        $data = "$iv_hex:$ciphertext_hex";
        return base64_encode($data);
    }

    /**
     * @param $ciphered
     * @return string
     */
    function decrypt($ciphered): string
    {
        $raw = base64_decode($ciphered);
        $data = explode(":", $raw);
        $iv = hex2bin($data[0]);
        $ciphertext = hex2bin($data[1]);
        return openssl_decrypt($ciphertext, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * @param $a
     * @return array
     */
    function array_encryp($a): array
    {
        $result = array();
        foreach ($a as $item)
            $result[] = $this->encrypt($item);
        return $result;
    }

    /**
     * @param $a
     * @return array
     */
    function array_decryp($a): array
    {
        $result = array();
        foreach ($a as $item)
            $result[] = $this->decrypt($item);
        return $result;
    }
}