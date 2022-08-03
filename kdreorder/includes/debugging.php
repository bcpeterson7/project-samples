<?php
/**
* Custom debugging / error logging scripts. This script separates out debugging logs from the standard
* WordPress debug.log and places the custom debugging log files outside of the servers document root, for security.
* Server permissions may need to be granted to allow writing to the ../log/ dir below the document root
*/

/**
* wlog() description: custom logger for debugging, development, and general logging
* @param  [string] $message : whatever is to be logged
* @param  [string] $overwrite : whether to save over the log file (true) or to append info to the end of the file (fales). Defaults to false
* @return bool
*/
if (!function_exists('wlog'))
{
  function wlog( $message, $overwrite=false ) : bool
  {
    $droot = $_SERVER['DOCUMENT_ROOT'];
    $log_root = str_replace("public", "log", $droot);
    $filename = $log_root . "/LOG_" . date('Y-m-d', time()) . ".log";
    $written = '';
    $date = DateTime::createFromFormat('U.u', microtime(TRUE));
    if ( is_bool($date) ) {
      $date = new DateTime();
      $date->setTimestamp( time() );
    }
    if ( $overwrite === false ) {
      $written = file_put_contents( $filename, "[" . date_format($date,'m/d/Y H:i:s.v') . "] " . $message . "\n", FILE_APPEND  );
    } else {
      $written = file_put_contents( $filename, "[" . date_format($date,'m/d/Y H:i:s.v') . "] " . $message . "\n"  );
    }
    // file_puit_contents returns the number of bytes that were written to the file, or FALSE on failure.
    $r = false;
    if ( false === $written ) {
      $r = false;
    } elseif ( $written > 0 ) {
      $r = true;
    }
    return $r;
  }
}



/**
 * array_dump_e description: writes to the custom log file what is being passed to the function
 * which is generally either an array or an object. Then this function writes the content of the
 * $array_or_object to the log file
 * @var [mixed] $array_or_object - should be an array or object
 * @var [string] $overwrite - whether to start the log file over (true) or to append information to the log file (false)
 */
if (!function_exists('array_dump_e')) {
  function array_dump_e($array_or_object, $overwrite = false) {
    wlog( "Item passed to array_dump_e() is: " . gettype($array_or_object), $overwrite );
    ob_start();
    print_r($array_or_object);
    $log = ob_get_contents();
    ob_end_clean();
    wlog($log);
  }
}
