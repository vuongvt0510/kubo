<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


if ( ! function_exists('force_download'))
{
    function force_download($filename = '', $data = '')
    {
        if ($filename == '' OR $data == '')
        {
            return FALSE;
        }

        // Try to determine if the filename includes a file extension.
        // We need it in order to set the MIME type
        if (FALSE === strpos($filename, '.'))
        {
            return FALSE;
        }

        // Grab the file extension
        $x = explode('.', $filename);
        $extension = end($x);

        $paths = array(
            SHAREDPATH.'config/mimes.php',
            SHAREDPATH.'config/'.ENVIRONMENT.'/mimes.php',
            APPPATH.'config/mimes.php',
            APPPATH.'config/'.ENVIRONMENT.'/mimes.php'
        );

        foreach ($paths as $f) {
            if (is_file($f)) {
                include $f;
            }
        }

        // Set a default mime if we can't find it
        if ( ! isset($mimes[$extension]))
        {
            $mime = 'application/octet-stream';
        }
        else
        {
            $mime = (is_array($mimes[$extension])) ? $mimes[$extension][0] : $mimes[$extension];
        }

        // Generate the server headers
        if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE") !== FALSE)
        {
            header('Content-Type: "'.$mime.'"');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header("Content-Transfer-Encoding: binary");
            header('Pragma: public');
            header("Content-Length: ".strlen($data));
        }
        else
        {
            header('Content-Type: "'.$mime.'"');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header("Content-Transfer-Encoding: binary");
            header('Expires: 0');
            header('Pragma: no-cache');
            header("Content-Length: ".strlen($data));
        }

        exit($data);
    }
}

/**
 * Export csv file function
 *
 * @param string $filename to dump
 * @param array $data
 */
if (!function_exists('force_download_csv')) {

    function force_download_csv($filename = '', $data = [])
    {
        $sep  = "\t";
        $eol  = "\n";
        $csv = '';

        foreach ($data AS $line) {

            foreach ($line AS &$l) {
                $l = preg_replace('/"/', '""', $l);
            }

            $csv .= '"'. implode('"'.$sep.'"', $line).'"'.$eol;
        }

        $encoded_csv = chr(255) . chr(254) . mb_convert_encoding($csv, 'UTF-16LE', 'UTF-8');

        return force_download($filename, $encoded_csv);
    }
}

