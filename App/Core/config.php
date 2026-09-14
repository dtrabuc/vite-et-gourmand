<?php

if ($_SERVER['SERVER_NAME'] == 'localhost') {
	/** database config LOCAL **/
	define('DB_NAME', 'my_db');
	define('DB_HOST', 'localhost');
	define('DB_USER', 'root');
	define('DB_PASSWORD', '');
	define('DB_DRIVER', '');

	define('BASE_URL', 'http://localhost');
	define('ROOT', 'http://localhost/MVC/public');

} else {
	/** database config**/
	define('DB_NAME', 'my_db');
	define('DB_HOST', 'localhost');
	define('DB_USER', 'root');
	define('DB_PASSWORD', '');
	define('DB_DRIVER', '');

	define('BASE_URL', 'http://localhost');
	define('ROOT', 'https://www.yourwebsite.com');

}

define('APP_NAME', "My Website");
define('APP_DESC', "Best website on the planet");

/** true means show errors **/
define('DEBUG', true);

/** Upload files config**/
define('DESTINATION_IMAGE_FOLDER', 'uploads/');
define('MAX_FILE_SIZE', 1048576);
define('ALLOWED_EXTENSIONS_FILE', ['png', 'jpeg', 'jpg']);
define('ALLOWED_MIME_TYPES', [
	'image/jpeg',
	'image/png',
	'image/gif',
	'image/bmp'
]);