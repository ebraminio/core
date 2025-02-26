<?php

namespace Webrium;

use Webrium\Url;
use Webrium\Debug;
use Webrium\File;

class App
{
  private static $rootPath = false, $local = 'en';

  public static function root($dir)
  {
    self::rootPath($dir);

    self::init_spl_autoload_register();

    File::runOnce(__DIR__ . '/lib/Helper.php');

    Url::ConfirmUrl();
  }

  public static function init_spl_autoload_register()
  {
    spl_autoload_register(function ($class) {

      if (substr($class, 0, 4) == 'App\\') {
        $class[0] = 'a';
      }

      $class = App::rootPath() . "/$class";
      $name = str_replace('\\', '/', $class) . ".php";

      if (File::exists($name)) {
        File::runOnce($name);
      } else {
        Debug::createError("Class '" . basename($class) . "' not found", false, false, 500);
      }
    });
  }

  public static function rootPath($dir = false)
  {
    if ($dir) {
      self::$rootPath = str_replace('\\', '/', realpath($dir) . '/');
    }

    return Url::without_trailing_slash(self::$rootPath);
  }


  public static function input($name = false, $default = null)
  {
    $method = Url::method();
    $params = [];

    if ($method == "GET") {
      $params = $_GET;
    } else if ($method == "POST") {
      if ($_SERVER["CONTENT_TYPE"] == 'application/json') {
        $params = json_decode(file_get_contents('php://input'), true);
      } else {
        $params = $_POST;
      }
    } else if ($method == "PUT" || $method == "DELETE") {
      parse_str(file_get_contents('php://input'), $params);
    }

    if ($name != false) {
      return $params[$name] ?? $default;
    }

    return $params;
  }

  public static function ReturnData($data)
  {
    if (is_array($data) || is_object($data)) {
      header('Content-Type: application/json ; charset=utf-8 ');
      $data = json_encode($data);
    }

    echo $data;
  }

  public static function setLocale($local)
  {
    self::$local = $local;
  }

  public static function isLocal($local)
  {
    return ($local == self::$local) ? true : false;
  }

  public static function getLocale()
  {
    return self::$local;
  }

  public static function disableCache()
  {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
  }

  private static $lang_store = [];
  public static function lang($name)
  {

    $arr = explode('.', $name);
    $file = $arr[0];
    $variable = $arr[1];

    if (!isset(self::$lang_store[$file])) {
      $path = Directory::path('langs');
      $lang = App::getLocale();

      $content = include_once("$path/$lang/$file.php");
      self::$lang_store[$file] = $content;
    }


    return self::$lang_store[$file][$variable] ?? false;
  }
}
