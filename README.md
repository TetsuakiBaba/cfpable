![CFPable Logo](logo.png)

# cfpable

A simple CFP (Call For Papers) editor and sharing it with others.

## try it out
You can try it out at [https://tetsuakibaba.jp/project/cfpable/](https://tetsuakibaba.jp/project/cfpable/)

## Installation

1. Clone the repository
```bash
git clone https://github.com/TetsuakiBaba/cfpable.git
cd cfpable
```

2. Create a file named `keys.php` by following the command below. Change the password as needed.
```bash
echo "<?php
define('ENCRYPTION_KEY', 'your_secure_encryption_key_here'); // 32byte
define('ENCRYPTION_IV', 'your_secure_iv_1');   // 16byte(fixed)
define('ADMIN_PASS', 'your_pass'); // Admin password
?>" > keys.php
```

3. Please upload the `cfpable` folder to your server and access it via your browser. The system needs php and a web server to run.

## Usage
1. Access the top page by going to `index.php`.
2. Enter Conference Name and press Begin.
3. Enter the details of the CFP.
4. Press Save to save the CFP.
5. Press Share to share the CFP with others.

## Dependencies
* php: 7.4 and above
* sqlite
* web server: apache, nginx, etc.

