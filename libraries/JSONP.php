<?php

declare(strict_types=1);
defined('BASEPATH') or exit('No direct script access allowed');

class JSONP
{

  private $data = null;
  private $buffer = null;
  private $pool = null;
  private $poolKey = null;

  const FIELD_REGEX = "/\w+/";
  const ARRAY_REGEX = "/^\w+\[/";
  const ALL_ARRAY_REGEX = "/^\w+\[\*\]/";
  const SPECIFIC_ARRAY_REGEX = "/^\w+\[\d+\]/";
  const RANGE_ARRAY_REGEX = "/^\w+\[(\d|):(\d|)\]/";

  /**
   * [parse description]
   * @param  [type] $data [description]
   * @return bool         [description]
   */
  public function parse(&$data): bool
  {
    if (is_scalar($data)) {
      $this->data = json_decode($data);
      if ($this->data == null || !is_object($this->data)) {
        $this->data = null;
        return false;
      }
      return true;
    }
    if (is_array($data)) {
      $this->data = &$data;
      //var_dump($this->data);
      return true;
    }
    return false;
  }
  /**
   * [set description]
   * @param string $path  [description]
   * @param [type] $value [description]
   */
  public function set(string $path, $value): void
  {
    $this->run_json_path($path);
    if ($this->pool == null) $this->buffer = $value;

    if ($this->poolKey == null) {
      for ($x = 0; count($this->pool); $x++) {
        $this->pool[array_keys($this->pool)[$x]] = $value;
      }
    }

    if (is_scalar($this->poolKey)) {
      $values = [];
      $this->recursive_scalar_key_build($values, $this->pool);
      //return $values;
    }
  }
  /**
   * [get description]
   * @param  string $path [description]
   * @return [type]       [description]
   */
  public function get(string $path)
  {
    $this->run_json_path($path);

    if ($this->pool == null) return $this->buffer;

    if ($this->poolKey == null) return $this->pool;

    if (is_scalar($this->poolKey)) {
      $values = [];
      var_dump($this->poolKey);
      $this->recursive_scalar_key_build($values, $this->pool);
      return $values;
    }

    // TODO: Recursive Array Key Build.
  }
  private function recursive_scalar_key_build(array &$values, &$pool): void
  {
    if (isset($pool[$this->poolKey])) {
      $values[] = &$pool[$this->poolKey];
    }
    for ($x = 0; $x < count($pool); $x++) {
      if (is_array($pool[array_keys($pool)[$x]])) {
        $this->recursive_scalar_key_build($values, $pool[array_keys($pool)[$x]]);
      }
    }
  }
  /**
   * [get_reference description]
   * @param  string $path [description]
   * @return [type]       [description]
   */
  public function &get_reference(string $path)
  {
    $this->run_json_path($path);

    if ($this->pool == null) return $this->buffer;

    if ($this->poolKey == null) return $this->pool;

    if (is_scalar($this->poolKey)) {
      $values = [];
      $this->recursive_scalar_key_build($values, $this->pool);
      return $values;
    }
  }
  /**
   * [run_path description]
   * @param string $path [description]
   */
  private function run_json_path(string $path): void
  {
    $this->buffer = &$this->data;
    unset($this->pool);
    $this->pool = null;
    $this->poolKey = null;
    $steps = explode('.', $path);
    foreach ($steps as $loopIndex => $step) {
      switch ($step) {
        case '$':
        case '':
          continue 2;
        default:

          if ($this->exact_match(self::FIELD_REGEX, $step)) {
            if ($this->pool == null) {
              $this->buffer = &$this->buffer[$step];
            } else {
              if ($this->poolKey == null) {
                $this->poolKey = $step;
              } else {
                $this->poolKey .= ".$step";
              }
            }
            if ($loopIndex == count($steps) - 1) {
              break;
            } else {
              continue 2;
            }
          }

          if ($step == '[*]' || $step == '*') {
            $this->pool = &$this->buffer;
            if ($loopIndex == count($steps) - 1) {
              break;
            } else {
              continue 2;
            }
          }

          if (preg_match(self::ARRAY_REGEX, $step)) {
            if (preg_match(self::SPECIFIC_ARRAY_REGEX, $step)) {
              list($key, $index) = $this->parse_specific_array_notation($step);
              if ($this->pool == null) {
                $this->buffer = &$this->buffer[$key][$index];
              } else {
                $this->poolKey = [$key, $index];
              }
              if ($loopIndex == count($steps) - 1) {
                break;
              } else {
                continue 2;
              }
            }
            if (preg_match(self::ALL_ARRAY_REGEX, $step)) {
              $this->pool = &$this->buffer[$this->get_key_from_notation($step)];
              if ($loopIndex == count($steps) - 1) {
                break;
              } else {
                continue 2;
              }
            }
          }
      }
    }
  }
  /**
   * [exact_match description]
   * @param  string $regex    [description]
   * @param  [type] $haystack [description]
   * @return bool             [description]
   */
  private function exact_match(string $regex, $haystack): bool
  {
    preg_match($regex, $haystack, $matches);
    return count($matches) > 0 && strlen($matches[0]) == strlen($haystack);
  }
  /**
   * [get_key_from_notation description]
   * @param  string $notation [description]
   * @return string           [description]
   */
  private function get_key_from_notation(string $notation): string
  {
    preg_match("/\w+/", $notation, $matches);
    return $matches[0];
  }
  /**
   * [parse_specific_array_notation description]
   * @param  string $notation [description]
   * @return array            [description]
   */
  private function parse_specific_array_notation(string $notation): array
  {
    preg_match("/\w+/", $notation, $matches);
    $key = $matches[0];
    $notation = preg_replace(self::ARRAY_REGEX, '', $notation);
    $notation = str_replace(']', '', $notation);
    return [$key, $notation];
  }
}
