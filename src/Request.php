<?php

namespace Link1515\HttpUtilsPhp5;

use Link1515\HttpUtilsPhp5\Constant\ContentType;
use Link1515\HttpUtilsPhp5\Utils\ArrayUtils;
use Link1515\HttpUtilsPhp5\Constant\Method;

/**
 * __get() magic method:
 * 
 * @method string getIp()
 * @method string getHost()
 * @method string getMethod()
 * @method array getHeaders(?string $dotNotation = null)
 * @method array|string getCookies(?string $dotNotation = null)
 * @method array|string getQueryString(?string $dotNotation = null)
 * @method array|string|null getBody(?string $dotNotation = null)
 * @method array|null getFiles(?string $dotNotation = null)
 */
class Request
{
  /**
   * @property self $instance
   */

  private static $instance;

  /**
   * @property string $ip
   */
  private $ip = '';

  /**
   * @property array $ipHeaderFilterChain
   */
  private static $ipFilterChain = [
    'HTTP_CLIENT_IP',
    'HTTP_X_FORWARDED_FOR',
    'REMOTE_ADDR'
  ];

  /**
   * @property string $host
   */
  private $host = '';

  /**
   * @property string $method
   */
  private $method = '';

  /**
   * @property array|null $cookies
   */
  private $cookies = null;

  /**
   * @property array|null $queryString 
   */
  private $queryString = null;

  /**
   * @property ?string $contentType 
   */
  private $contentType = null;

  /**
   * @property array $headers
   */
  private $headers = [];

  /**
   * @property string|array|null $body
   */
  private $body = null;

  /**
   * @property array|null $file
   */
  private $files = null;

  private function __construct()
  {
    $this->host = $_SERVER['SERVER_NAME'];
    $this->method = $_SERVER['REQUEST_METHOD'];
    if (count($_COOKIE) > 0) {
      $this->cookies = $_COOKIE;
    }
    $this->contentType = isset($_SERVER['CONTENT_TYPE']) ? explode(';', $_SERVER['CONTENT_TYPE'])[0] : null;
    $this->headers = getallheaders();

    if (isset($_SERVER['QUERY_STRING'])) {
      parse_str($_SERVER['QUERY_STRING'], $this->queryString);
    }

    $this->bindIp();
    $this->bindRequestBody();
  }

  private function bindIp()
  {
    foreach (self::$ipFilterChain as $header) {
      if (isset($_SERVER[$header])) {
        $this->ip = $_SERVER[$header];
        break;
      }
    }
  }

  private function bindRequestBody()
  {
    if (in_array($this->method, [Method::POST, Method::PUT, Method::PATCH])) {
      $rawBody = file_get_contents('php://input');

      switch ($this->contentType) {
        case ContentType::JSON:
          $this->body = json_decode($rawBody, true);
          break;

        case ContentType::FORM_DATA:
          $this->parseFormData($rawBody);
          break;

        case ContentType::X_WWW_FORM_URLENCODED:
          parse_str($rawBody, $this->body);
          break;

        default:
          if (strlen($rawBody) > 0) {
            $this->body = $rawBody;
          }
          break;
      }
    }
  }

  /**
   * @param string $rawRequestBody
   */
  private function parseFormData($rawRequestBody)
  {
    if ($this->method === Method::POST) {
      if (count($_POST) > 0) {
        $this->body = $_POST;
      }
      if (count($_FILES) > 0) {

        $this->files = $_FILES;
      }
      return;
    }

    $boundary = substr($rawRequestBody, 0, strpos($rawRequestBody, "\r\n"));

    $parts = array_slice(explode($boundary, $rawRequestBody), 1);
    $data = [];
    $files = [];

    foreach ($parts as $part) {
      if ($part === "--\r\n") {
        break;
      }

      // Separate content from headers
      $part = trim($part, "\r\n");
      list($rawHeaders, $body) = explode("\r\n\r\n", $part, 2);

      // Parse the headers list
      $rawHeaders = explode("\r\n", $rawHeaders);
      $headers = [];
      foreach ($rawHeaders as $header) {
        list($name, $value) = explode(':', $header);
        $headers[strtolower($name)] = ltrim($value, ' ');
      }

      // Parse the Content-Disposition to get the field name, etc.
      if (isset($headers['content-disposition'])) {
        preg_match(
          '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
          $headers['content-disposition'],
          $matches
        );
        $fieldName = $matches[2];
        $filename = isset($matches[4]) ? $matches[4] : null;

        //Parse File
        if (isset($filename)) {
          //get tmp name
          $filenameParts = pathinfo($filename);
          $tmpName = tempnam(sys_get_temp_dir(), $filenameParts['filename']);

          $files[$fieldName] = [
            'name' => $filename,
            'full_path' => $filename,
            'type' => $value,
            'tmp_name' => $tmpName,
            'error' => 0,
            'size' => strlen($body),
          ];

          //place in temporary directory
          file_put_contents($tmpName, $body);
        }
        //Parse Field
        else {
          $data[$fieldName] = $body;
        }
      }
    }

    if (count($data) > 0) {
      $this->body = $data;
    }

    if (count($files) > 0) {
      $this->files = $files;
    }
  }

  public static function getInstance()
  {
    if (!isset(self::$instance)) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /**
   * @param array $ipFilterChain
   */
  public static function setIpFilterChain(array $ipFilterChain)
  {
    self::$ipFilterChain = $ipFilterChain;
  }

  /**
   * @param string $methodName
   * @param array $args
   */
  public function __call($methodName, $args)
  {
    if (method_exists($this, $methodName)) {
      return call_user_func_array([$this, $methodName], $args);
    } else if (preg_match("/^get.*/", $methodName)) {
      // getter
      $propName = lcfirst(preg_replace('/^get(.*)/', '$1', $methodName));

      if (!property_exists($this, $propName)) {
        return null;
      }

      if (count($args) === 1 && is_string($args[0])) {
        return ArrayUtils::getByDotNotaion($this->$propName, $args[0]);
      }

      return $this->$propName;
    }

    return null;
  }

  public function toArray()
  {
    return [
      'ip' => $this->ip,
      'host' => $this->host,
      'method' => $this->method,
      'cookies' => $this->cookies,
      'queryString' => $this->queryString,
      'contentType' => $this->contentType,
      'headers' => $this->headers,
      'body' => $this->body,
      'files' => $this->files
    ];
  }

  public function __toString()
  {
    return json_encode($this->toArray());
  }
}
