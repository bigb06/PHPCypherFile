
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


## 🙇 Author
- [Nicolas Chevallier](https://www.linkedin.com/in/nicolas-chevallier-525677/)
     
## 🙇 Acknowledgements      
- [Antoine Lame](https://medium.com/@antoine.lame/how-to-encrypt-files-with-php-f4adead297de) for the inital idea

## License
This project is open source and available under the [MIT License](LICENSE).