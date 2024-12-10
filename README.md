
# PHPCypherFile
PHPCypherFile provides a robust solution for encrypting large files securely without significant memory overhead. It combines the power of RSA for public/private key encryption and AES-256-CBC for symmetric encryption, ensuring both performance and security.


## Features

1. Large File Support:

    Files are encrypted in chunks, minimizing memory usage during the encryption/decryption process. This ensures scalability for handling large files.

2. Hybrid Encryption Scheme:

    - random symmetric key is generated for AES-256-CBC encryption of the file content.
    - The symmetric key and IV (Initialization Vector) are encrypted with a public RSA key.
	
3.	Output Structure:
The encrypted file includes the following components:
    - RSA-encrypted symmetric key.
    - RSA-encrypted IV.
    - AES-encrypted file data.

4.	Security Standards:
    - AES-256-CBC ensures high-speed, secure encryption for the data.
    - RSA encryption secures the transmission of the symmetric key, leveraging a public/private key pair.

5.	Minimal Memory Footprint:

    By processing files in small chunks, the class avoids loading the entire file into memory.


## Installation
To install this library, you can use Composer. Run the following command:

```bash
composer require bigb06/phpcypherfile
```

## Example

Basic code to use PHPCypherFile (see example.php for full example and keys generation):

```php

use PHPCypherFile\PHPCypherFile;

// Encrypt the file
PHPCypherFile::encryptFile($inputFile, $encryptedFile,$publicKey);

// Decrypt the file
PHPCypherFile::decryptFile($encryptedFile, $decryptedFile,$privateKey);
```

### Keys Generation
```php
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
```
You can also refer to example.php for detailed steps on how to generate the required RSA public/private keys for encryption and decryption.

## 🙇 Author
- [Nicolas Chevallier](https://www.nicolas-chevallier.fr/) [Linkedin Bio](https://www.linkedin.com/in/nicolas-chevallier-525677/)
     
## 🙇 Acknowledgements      
- [Antoine Lame](https://medium.com/@antoine.lame/how-to-encrypt-files-with-php-f4adead297de) for the inital idea

## License
This project is open source and available under the [MIT License](LICENSE).