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

final class PHPCypherFile
{
    // The number of bytes to read from the file for each chunk
    const FILE_ENCRYPTION_BLOCKS = 8192;
    const CYPHER = 'aes-256-cbc';
    /**
     * Don't allow this to be instantiated.
     *
     * @throws Error
     * @codeCoverageIgnore
     */
    final private function __construct()
    {
        throw new Error('Do not instantiate');
    }


    public static function encryptFile($inputFile, $outputFile, $publicKey) {
        if(!extension_loaded('openssl')) {
            throw new Error('OpenSSL is not installed');
        }

        if ($publicKey === false) {
            throw new Error('Invalid public key');
       }
   
       $fpSource = fopen($inputFile, 'rb');
       if (!$fpSource) {
            throw new Error('Error opening input file '.$inputFile);
       }

       $fpDest = fopen($outputFile, 'wb');
       if (!$fpDest) {
            throw new Error('Error opening output file '.$outputFile);
       }
       
       $ivLength = openssl_cipher_iv_length(self::CYPHER);
       $iv = openssl_random_pseudo_bytes($ivLength);
   
       $keyLength = 32; // 256-bit key
       $symmetricKey = openssl_random_pseudo_bytes($keyLength);
   
       // Encrypt the symmetric key with the public key
       openssl_public_encrypt($symmetricKey, $encryptedSymmetricKey, $publicKey);
   
   
       // Write the encrypted symmetric key to the destination file
       if(fwrite($fpDest, $encryptedSymmetricKey) === FALSE){
            throw new Error('Error writing encrypted symmetric key to the destination file '.$outputFile);
       }
   
       // Write the IV to the destination file
       fwrite($fpDest, $iv);
   
       while (!feof($fpSource)) {
           $plaintext = fread($fpSource, $ivLength * self::FILE_ENCRYPTION_BLOCKS);
           $ciphertext = openssl_encrypt($plaintext, self::CYPHER, $symmetricKey, OPENSSL_RAW_DATA, $iv);
           $iv = substr($ciphertext, 0, $ivLength);
           fwrite($fpDest, $ciphertext);
       }
   
       fclose($fpSource);
       fclose($fpDest);
    }

    public static function decryptFile($inputFile, $outputFile, $privateKey) {
        if(!extension_loaded('openssl')) {
            throw new Error('OpenSSL is not installed');
        }

        if ($privateKey === false) {
            throw new Error('Invalid private key');
        }

        $ivLength = openssl_cipher_iv_length(self::CYPHER);
        $fpSource = fopen($inputFile, 'rb');
        if (!$fpSource) {
            throw new Error('Error opening input file '.$inputFile);
        }

        $fpDest = fopen($outputFile, 'wb');
        if (!$fpDest) {
            throw new Error('Error opening output file '.$outputFile);
        }

        // Read the encrypted symmetric key from the source file
        $encryptedSymmetricKey = fread($fpSource, 512);

        // Decrypt the encrypted symmetric key with the private key
        if(openssl_private_decrypt($encryptedSymmetricKey, $symmetricKey, $privateKey) === FALSE){
            throw new Error('Error decrypting the encrypted symmetric key with the private key');
        }

        // Read the IV from the source file
        $iv = fread($fpSource, $ivLength);

        while (!feof($fpSource)) {
            $ciphertext = fread($fpSource, $ivLength * (self::FILE_ENCRYPTION_BLOCKS + 1));
            $plaintext = openssl_decrypt($ciphertext, self::CYPHER, $symmetricKey, OPENSSL_RAW_DATA, $iv);
            $iv = substr($ciphertext, 0, $ivLength);
            fwrite($fpDest, $plaintext);
        }

        fclose($fpSource);
        fclose($fpDest);
    }
}
?>