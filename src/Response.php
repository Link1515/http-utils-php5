<?php

namespace Link1515\HttpUtilsPhp5;

use Link1515\HttpUtilsPhp5\Constant\ContentType;

class Response
{
  /**
   * @param string $name
   * @param string $value
   * @param bool $replace 
   * @return void
   */
  public static function setHeader($name, $value, $replace = true)
  {
    header($name . ': ' . $value, $replace);
  }

  /**
   * @param array $headers
   * @param bool $replace 
   * @return void
   */
  public static function setHeaders($headers, $replace = true)
  {
    foreach ($headers as $name => $value) {
      header($name . ': ' . $value, $replace);
    }
  }

  /**
   * @param int $code
   * @return void
   */
  public static function status($code)
  {
    http_response_code($code);
  }

  /**
   * @param string $url
   * @return void
   */
  public static function redierct($url, $statusCode = 301)
  {
    self::status($statusCode);
    header('Location: ' . $url);
    exit;
  }

  /**
   * @param array $data
   * @param int $status
   * @return void
   */
  public static function json($data, $statusCode = 200)
  {
    self::status($statusCode);
    self::setHeader('Content-Type', ContentType::JSON);
    echo json_encode($data);
    exit;
  }

  /**
   * @param string $data
   * @param int $status
   * @return void
   */
  public static function send($data, $statusCode = 200)
  {
    self::status($statusCode);
    echo $data;
    exit;
  }
}
