<?php

/**
 * @name       php mysql backup with "half" a versioning system
 * @author     Ryan Harkins <silverphp@gmail.com>
 * @copyright  2011 Ryan Harkins
 * @license    see the LICENSE file included in the distribution
 *
 */

// Backup config
define('BACKUP_COUNT', 10);
define('PATH', "./backups");
define('LOCKED_PATH', PATH . ".locked");

// Database config
define('DB_HOST', "HOSTNAME");
define('DB_USER', "USERNAME");
define('DB_PASS', "PASSWORD");
define('DB_NAME', "DBNAME");


// Name of the file to work in
$locked_path = PATH . '.locked';

if (!ini_get('safe_mode')) {
    set_time_limit(120);
}

// Checking if diorectory is locked
if (file_exists(LOCKED_PATH)) {
    echo 'BACKUPS LOCKED';
    exit;
}

// Create the backups directory if it's the first time
if (!file_exists(PATH)) {
    $command = "mkdir " . PATH;
    exec($command);
}

// Locking directory
$command = "mv " . PATH . " " . LOCKED_PATH;
exec($command);

/* * **************************** Backup Logic ********************************* */

if ($handle = opendir(LOCKED_PATH)) {

    $files = array();

    while (false !== ($file = readdir($handle))) {
        if ($file != '..' && $file != '.' && is_numeric($file))
            $files[] = $file;
    }

    sort($files);

    // If the backup limit has been reached:
    // rename all the folders -1 and add make another to put backup in
    if (count($files) == BACKUP_COUNT) {

        // Remove first backup
        $command = "rm -rf " . LOCKED_PATH . "/" . $files[0];
        exec($command);

        // Rename files -1
        foreach ($files as $file) {
            $command = "mv " . LOCKED_PATH . "/" . $file . " " . LOCKED_PATH . "/" . ($file - 1);
            exec($command);
        }

        // Run Backup
        $backup_file = end($files);
        create_backup($backup_file, LOCKED_PATH);
    } else {

        // Run Backup
        $backup_file = end($files);
        create_backup($backup_file + 1, LOCKED_PATH);
    }

    // All done, just copy to real path and remove the locked dir!
    closedir($handle);

    $command = "cp -r " . LOCKED_PATH . " " . PATH;
    exec($command);

    if (file_exists(PATH)) {
        $command = "rm -rf " . LOCKED_PATH;
        exec($command);
    }
}


function create_backup($backup_file, $path) {

    // Create directory to put the backup in
    $command = "mkdir " . $path . "/" . $backup_file;
    exec($command);

    // Connect to db
    mysql_connect(DB_HOST, DB_USER, DB_PASS) or die(mysql_error());
    mysql_select_db(DB_NAME);

    // chmod the directory so you can write to it
    $backup_file = $path . "/" . $backup_file . "/" . DB_NAME . "-" . date("Y-m-d-H-i-s") . '.gz';

    // mysqldump command preperations
    $command = "mysqldump --host=" . DB_HOST . " --user=" . DB_USER . " --password=" . DB_PASS . " --databases " . DB_NAME . " | gzip > " . $backup_file;

    // You need to have access to the system command or exec($command);
    system($command);

    mysql_close();
    return;
}
?>
