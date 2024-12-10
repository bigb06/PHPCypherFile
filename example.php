<?php
require_once 'vendor/autoload.php';

use PHPCypherFile\PHPCypherFile;

// If you want to load your already saved keys, use openssl_pkey_get_private() and openssl_pkey_get_public() directly
// private_key_file = 'PATH TO PRIVATE KEY';
// $privateKey = openssl_pkey_get_private(file_get_contents($private_key_file));
// $public_key_file = 'PATH TO PUBLIC KEY';
// $publicKey = openssl_pkey_get_public(file_get_contents($public_key_file));

// or Generate a new key pair
$config = [
    "private_key_bits" => 4096,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
];
$res = openssl_pkey_new($config);
openssl_pkey_export($res, $privateKeyString);
$details = openssl_pkey_get_details($res);
$publicKeyString = $details["key"];
$publicKey = openssl_pkey_get_public($publicKeyString);
$privateKey = openssl_pkey_get_private($privateKeyString);

openssl_pkey_get_details($publicKey);
$details = openssl_pkey_get_details($publicKey);
echo "Public Key length: " . $details['bits'] . "\n";

openssl_pkey_get_details($privateKey);
$details = openssl_pkey_get_details($privateKey);
echo "Private Key length: " . $details['bits'] . "\n";


// download video to encrypt
$url = "https://download.blender.org/peach/bigbuckbunny_movies/BigBuckBunny_320x180.mp4";
$inputFile = tempnam(sys_get_temp_dir(), 'vid');
echo "Download file from url: " . $url. "\n";
file_put_contents($inputFile, file_get_contents($url), LOCK_EX);
echo "Downloaded file size: " . number_format(filesize($inputFile)/ 1048576, 2) . " MB\n";

// Paths for the encrypted and decrypted files
$encryptedFile = tempnam(sys_get_temp_dir(), 'bin');
$decryptedFile = tempnam(sys_get_temp_dir(), 'bin');

$encryptedFile = "/Users/Nicolas/Downloads/BigBunnycrypt.mp4";
$decryptedFile = "/Users/Nicolas/Downloads/BigBunnydecrypt.mp4";


// Measure time before encryption
$timeStart = microtime(true);

// Encrypt the file
PHPCypherFile::encryptFile($inputFile, $encryptedFile,$publicKey);

// Measure time after encryption
$timeEnd = microtime(true);

// Display time used for encryption
echo "Encryption time: " . ($timeEnd - $timeStart) . " seconds\n";
echo "Encrypted File size:".number_format(filesize($encryptedFile)/ 1048576, 2) . " MB\n";

// Measure time before decryption
$timeStart = microtime(true);

// Decrypt the file
PHPCypherFile::decryptFile($encryptedFile, $decryptedFile,$privateKey);

// Measure time after decryption
$timeEnd = microtime(true);
$memoryEnd = memory_get_usage(false);

// Display time used for decryption
echo "Decryption time: " . ($timeEnd - $timeStart) . " seconds\n";

// Calculate and display checksums
$originalChecksum = sha1_file($inputFile);
$decryptedChecksum = sha1_file($decryptedFile);

echo "Original file checksum: " . $originalChecksum . " - size:".number_format(filesize($inputFile)/ 1048576, 2) . " MB\n";
echo "Decrypted file checksum: " . $decryptedChecksum . " - size:".number_format(filesize($decryptedFile)/ 1048576, 2) . " MB\n";

// Verify the integrity of the video
if ($originalChecksum == $decryptedChecksum) {
    echo "The original and decrypted files are identical\n";
} else {
    echo "The original and decrypted files are different\n";
}

// Clean up the temporary files 
if (strpos($inputFile, sys_get_temp_dir()) !== false){
    unlink($inputFile);
}
if (strpos($encryptedFile, sys_get_temp_dir()) !== false){
    unlink($encryptedFile);
}
if (strpos($decryptedFile, sys_get_temp_dir()) !== false){
    unlink($decryptedFile);
}
?>