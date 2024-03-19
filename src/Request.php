<?php

declare(strict_types=1);

namespace Link1515\HttpUtils;

use Link1515\HttpUtils\Constant\ContentType;
use Link1515\HttpUtils\Utils\ArrayUtils;
use Link1515\HttpUtils\Constant\Method;

/**
 * __get() magic method:
 * 
 * @method string getIp()
 * @method string getHost()
 * @method string getMethod()
 * @method string getHeaders(?string $dotNotation = null)
 * @method array|string getCookie(?string $dotNotation = null)
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
   * @property string $host
   */
  private $host = '';

  /**
   * @property string $method
   */
  private $method = '';

  /**
   * @property array $cookie
   */
  private $cookie = [];

  /**
   * @property array $queryString 
   */
  private $queryString = [];

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
    $this->cookie = $_COOKIE;
    $this->contentType = isset($_SERVER['CONTENT_TYPE']) ? explode(';', $_SERVER['CONTENT_TYPE'])[0] : null;
    $this->headers = getallheaders();

    $ipHeaders = [
      'HTTP_AKACIP',
      'HTTP_VERCIP',
      'HTTP_ECCIP',
      'HTTP_L7CIP',
      'HTTP_CLIENT_IP',
      'HTTP_X_FORWARDED_FOR',
      'HTTP_X_FORWARDED',
      'HTTP_X_CLUSTER_CLIENT_IP',
      'HTTP_FORWARDED_FOR',
      'HTTP_FORWARDED',
      'REMOTE_ADDR'
    ];
    foreach ($ipHeaders as $header) {
      if (isset($_SERVER[$header])) {
        $this->ip = $_SERVER[$header];
        break;
      }
    }

    if (isset($_SERVER['QUERY_STRING'])) {
      parse_str($_SERVER['QUERY_STRING'], $this->queryString);
    }

    // bind request body
    if (in_array($this->method, [Method::POST, Method::PUT, Method::PATCH])) {
      $rawBody = file_get_contents('php://input');

      switch ($this->contentType) {
        case ContentType::JSON:
          $this->body = json_decode($rawBody, true);
          break;

        case ContentType::FORM_DATA:
          $this->parseFormData($rawBody);
          break;

        default:
          if (strlen($rawBody) > 0) {
            $this->body = $rawBody;
          }
          break;
      }
    }
  }

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
      'cookie' => $this->cookie,
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