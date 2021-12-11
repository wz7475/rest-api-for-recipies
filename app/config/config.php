<?php
$cleardb_url = parse_url(getenv("CLEARDB_DATABASE_URL"));
$cleardb_server = $cleardb_url["host"];
$cleardb_username = $cleardb_url["user"];
$cleardb_password = $cleardb_url["pass"];
$cleardb_db = substr($cleardb_url["path"], 1);
//Database params
define('DB_HOST', $cleardb_server); //Add your db host
define('DB_USER', $cleardb_username); // Add your DB root
define('DB_PASS', $cleardb_password); //Add your DB pass
define('DB_NAME', $cleardb_db); //Add your DB Name
// define('DB_HOST', 'localhost'); //Add your db host
// define('DB_USER', 'root'); // Add your DB root
// define('DB_PASS', ''); //Add your DB pass
// define('DB_NAME', 'myblog'); //Add your DB Name
//APPROOT
define('APPROOT', dirname(dirname(__FILE__)));
//URLROOT (Dynamic links)
define('URLROOT', 'https://granny-crud.herokuapp.com');
//Sitename
define('SITENAME', 'Login & Register script');