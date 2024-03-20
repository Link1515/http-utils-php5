# http-utils-php5

For users working with PHP 5.x environments, this package provides a convenient solution. It provides encapsulated utilities for handling requests and responses.

## Installation

```bash
composer require link1515/http-utils-php5
```

## Usage

### Request

<table>
  <thead>
    <tr>
      <th>Return type</th>
      <th>Method</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>string</td>
      <td>getIp()</td>
    </tr>
    <tr>
      <td>string</td>
      <td>getHost()</td>
    </tr>
    <tr>
      <td>string</td>
      <td>getMethod()</td>
    </tr>
    <tr>
      <td>array</td>
      <td>getHeaders(?string $dotNotation = null)</td>
    </tr>
    <tr>
      <td>array|string</td>
      <td>getCookies(?string $dotNotation = null)</td>
    </tr>
    <tr>
      <td>array|string</td>
      <td>getQueryString(?string $dotNotation = null)</td>
    </tr>
    <tr>
      <td>array|string|null</td>
      <td>getBody(?string $dotNotation = null)</td>
    </tr>
    <tr>
      <td>array|null</td>
      <td>getFiles(?string $dotNotation = null)</td>
    </tr>
    <tr>
      <td>array</td>
      <td>toArray()</td>
    </tr>
  </tbody>
</table>

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Link1515\HttpUtilsPhp5\Request;

// get the request instance
$request = Request::getInstance();

// get property
$ip = $request->getIp();
$method = $request->getMethod();

// get property of the array format
// ex: https://example.com?name=Lynk&job=developer
$queryString = $request->getQueryString(); // ['name' => 'Lynk', 'job' => 'developer']
// You can specify a key to get a value
$name = $request->getQueryString('name'); // Lynk

// You can even use dot notation to get nested values
// ex: request body: { user: { order: { id: 123 }}}
$orderId = $request->getBody('user.order.id'); // 123
```

Configure ipHeaderFilterChain

```php
// You can configure the ipHeaderFilterChain by yourself. Headers earlier in the array are adopted first.
Request::setIpHeaderFilterChain([
  'HTTP_CLIENT_IP',
  'HTTP_X_FORWARDED_FOR',
  'REMOTE_ADDR'
]);
```

### Response

- Response::setHeader(string $name, string $value, bool $replace = true)
- Response::setHeaders(array $headers, bool $replace = true)
- Response::status(int $code)
- Response::redierct(string $url, int $statusCode = 301)
- Response::json(array $data, int $statusCode = 200)
- Response::send(string $data, int $statusCode = 200)

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Link1515\HttpUtilsPhp5\Response;
use Link1515\HttpUtilsPhp5\Constant\Status;

Response::json(['message' => 'not found'], Status::NOT_FOUND);
```

## Constant

This package also provides some common constant.

- Link1515\HttpUtilsPhp5\Constant\Status
- Link1515\HttpUtilsPhp5\Constant\Method
- Link1515\HttpUtilsPhp5\Constant\ContentType
