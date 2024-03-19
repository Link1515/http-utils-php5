<?php

declare(strict_types=1);

namespace Link1515\HttpUtils\Utils;

class ArrayUtils
{

  /**
   * @param array $arr
   * @param string $notation
   * @return mixed
   */
  public static function getByDotNotaion($arr, $notation)
  {
    if (!is_array($arr))
      return null;

    $path = explode('.', $notation);

    $currentValue = $arr;
    for ($i = 0; $i < count($path); $i++) {
      if (!array_key_exists($path[$i], $currentValue))
        return null;
      $currentValue = $currentValue[$path[$i]];
    }

    return $currentValue;
  }
}