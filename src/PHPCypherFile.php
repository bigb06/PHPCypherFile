<?php
/**
 * PHPCypherFile.php
 *
 * PHP version 8.2
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * MIT License for more details.
 *
 * You should have received a copy of the MIT License
 * along with this program.  If not, see <https://opensource.org/licenses/MIT>.
 *
 * @category PHP
 * @package  PHPCypherFile
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/bigb06/PHPCypherFile 
 */

namespace PHPCypherFile;

use RuntimeException;
use InvalidArgumentException;

final class PHPCypherFile
{
    // The number of bytes to read from the file for each chunk
    const FILE_ENCRYPTION_BLOCKS = 8192;
    const CYPHER = 'aes-256-cbc';

    /**
     * Don't allow this to be instantiated.
     *
     * @throws RuntimeException
     * @codeCoverageIgnore
     */
    final private function __construct()
    {
        throw new RuntimeException('Do not instantiate');
    }

    /**
     * Encrypt a file using a public key.
     *
     * @param string $inputFile  The path to the input file.
     * @param string $outputFile The path to the output file.
     * @param string $publicKey  The public key.
     *
     * @throws RuntimeException If an error occurs during encryption.
     * @throws InvalidArgumentException If an invalid argument is provided.
     */
    public static function encryptFile($inputFile, $outputFile, $publicKey) {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException('OpenSSL is not installed');
        }

        if ($publicKey === false) {
            throw new InvalidArgumentException('Invalid public key');
        }

        $fpSource = fopen($inputFile, 'rb');
        if (!$fpSource) {
            throw new RuntimeException('Error opening input file ' . $inputFile);
        }

        $fpDest = fopen($outputFile, 'wb');
        if (!$fpDest) {
            fclose($fpSource);
            throw new RuntimeException('Error opening output file ' . $outputFile);
        }

        try {
            $ivLength = openssl_cipher_iv_length(self::CYPHER);
            $iv = random_bytes($ivLength);

            $keyLength = 32; // 256-bit key
            $symmetricKey = random_bytes($keyLength);

            // Encrypt the symmetric key with the public key
            if (openssl_public_encrypt($symmetricKey, $encryptedSymmetricKey, $publicKey) === false) {
                throw new RuntimeException('Error encrypting the symmetric key with the public key: ' . openssl_error_string());
            }

            // Write the encrypted symmetric key and the IV to the destination file
            self::write($fpDest, $encryptedSymmetricKey, $outputFile);
            self::write($fpDest, $iv, $outputFile);

            while (!feof($fpSource)) {
                $plaintext = fread($fpSource, $ivLength * self::FILE_ENCRYPTION_BLOCKS);
                if ($plaintext === false) {
                    throw new RuntimeException('Error reading input file ' . $inputFile);
                }
                $ciphertext = openssl_encrypt($plaintext, self::CYPHER, $symmetricKey, OPENSSL_RAW_DATA, $iv);
                if ($ciphertext === false) {
                    throw new RuntimeException('Error encrypting data: ' . openssl_error_string());
                }
                $iv = substr($ciphertext, 0, $ivLength);
                self::write($fpDest, $ciphertext, $outputFile);
            }
        } finally {
            fclose($fpSource);
            fclose($fpDest);
        }
    }

    /**
     * Decrypt a file using a private key.
     *
     * @param string $inputFile  The path to the input file.
     * @param string $outputFile The path to the output file.
     * @param string $privateKey The private key.
     *
     * @throws RuntimeException If an error occurs during decryption.
     * @throws InvalidArgumentException If an invalid argument is provided.
     */
    public static function decryptFile($inputFile, $outputFile, $privateKey) {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException('OpenSSL is not installed');
        }

        if ($privateKey === false) {
            throw new InvalidArgumentException('Invalid private key');
        }

        $ivLength = openssl_cipher_iv_length(self::CYPHER);
        $fpSource = fopen($inputFile, 'rb');
        if (!$fpSource) {
            throw new RuntimeException('Error opening input file ' . $inputFile);
        }

        $fpDest = fopen($outputFile, 'wb');
        if (!$fpDest) {
            fclose($fpSource);
            throw new RuntimeException('Error opening output file ' . $outputFile);
        }

        try {
            // Read the encrypted symmetric key from the source file
            $encryptedSymmetricKey = fread($fpSource, 512);

            // Decrypt the encrypted symmetric key with the private key
            if ($encryptedSymmetricKey === false || openssl_private_decrypt($encryptedSymmetricKey, $symmetricKey, $privateKey) === false) {
                throw new RuntimeException('Error decrypting the encrypted symmetric key with the private key');
            }

            // Read the IV from the source file
            $iv = fread($fpSource, $ivLength);
            if ($iv === false || strlen($iv) !== $ivLength) {
                throw new RuntimeException('Error reading the IV from input file ' . $inputFile);
            }

            while (!feof($fpSource)) {
                $ciphertext = fread($fpSource, $ivLength * (self::FILE_ENCRYPTION_BLOCKS + 1));
                if ($ciphertext === false) {
                    throw new RuntimeException('Error reading input file ' . $inputFile);
                }
                if ($ciphertext === '') {
                    break;
                }
                $plaintext = openssl_decrypt($ciphertext, self::CYPHER, $symmetricKey, OPENSSL_RAW_DATA, $iv);
                if ($plaintext === false) {
                    throw new RuntimeException('Error decrypting data, the file may be corrupted: ' . openssl_error_string());
                }
                $iv = substr($ciphertext, 0, $ivLength);
                self::write($fpDest, $plaintext, $outputFile);
            }
        } finally {
            fclose($fpSource);
            fclose($fpDest);
        }
    }

    /**
     * @param resource $fp
     *
     * @throws RuntimeException If the data could not be fully written.
     */
    private static function write($fp, string $data, string $file): void
    {
        if (fwrite($fp, $data) !== strlen($data)) {
            throw new RuntimeException('Error writing to the destination file ' . $file);
        }
    }
}