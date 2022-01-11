<?php
//APPROOT
define('APPROOT', dirname(dirname(__FILE__)));

if(APPROOT == "D:\Programming\gr9k\granny-grud\app")
{
    define('DB_HOST', 'eu-cdbr-west-02.cleardb.net'); //Add your db host
    define('DB_USER', 'bf6d0ebec5a2f9'); // Add your DB root
    define('DB_PASS', '1c957d41'); //Add your DB pass
    define('DB_NAME', 'heroku_5c1b88cec025cd7'); //Add your DB Name

    define('URLROOT', 'http://localhost/');
}
else
{
    $cleardb_url = parse_url(getenv("CLEARDB_DATABASE_URL"));
    $cleardb_server = $cleardb_url["host"];
    $cleardb_username = $cleardb_url["user"];
    $cleardb_password = $cleardb_url["pass"];
    $cleardb_db = substr($cleardb_url["path"], 1);
    
    // Database params
    define('DB_HOST', $cleardb_server); //Add your db host
    define('DB_USER', $cleardb_username); // Add your DB root
    define('DB_PASS', $cleardb_password); //Add your DB pass
    define('DB_NAME', $cleardb_db); //Add your DB Name

    define('URLROOT', 'https://granny-crud.herokuapp.com');
}
//Sitename
define('SITENAME', 'Login & Register script');