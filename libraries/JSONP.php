<?php
declare(strict_types=1);
defined('BASEPATH') or exit('No direct script access allowed');

class JSONP
{

  private $data = null;
  private $buffer = null;

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
  public function parse(&$data):bool
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
      $this->data =& $data;
      var_dump($this->data);
      return true;
    }
    return false;
  }
  /**
   * [set description]
   * @param string $path  [description]
   * @param [type] $value [description]
   */
  public function set(string $path, $value):void
  {
    $this->run_json_path($path);
    $this->buffer = $value;
  }
  /**
   * [get description]
   * @param  string $path [description]
   * @return [type]       [description]
   */
  public function get(string $path)
  {
    $this->run_json_path($path);
    return $this->buffer;
  }
  /**
   * [run_path description]
   * @param string $path [description]
   */
  private function run_json_path(string $path):void
  {
    $this->buffer =& $this->data;
    $steps = explode('.', $path);
    foreach ($steps as $loopIndex => $step) {
      switch ($step) {
        case '$':
        case '':
          continue;
        default:
          if ($this->exact_match(self::FIELD_REGEX, $step)) {
            $this->buffer =& $this->buffer[$step];
            if ($loopIndex == count($steps) - 1) { break; } else { continue; }
          }
          if (preg_match(self::ARRAY_REGEX, $step)) {
            if (preg_match(self::SPECIFIC_ARRAY_REGEX, $step)) {
              list($key, $index) = $this->parse_specific_array_notation($step);
              $this->buffer =& $this->buffer[$key][$index];
              if ($loopIndex == count($steps) - 1) { break; } else { continue; }
            }
            if (preg_match(self::ALL_ARRAY_REGEX, $step)) {
              $this->buffer =& $this->buffer[$this->get_key_from_notation($step)];
              if ($loopIndex == count($steps) - 1) { break; } else { continue; }
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
  private function exact_match(string $regex, $haystack):bool
  {
    preg_match($regex, $haystack, $matches);
    return strlen($matches[0]) == strlen($haystack);
  }
  /**
   * [get_key_from_notation description]
   * @param  string $notation [description]
   * @return string           [description]
   */
  private function get_key_from_notation(string $notation):string
  {
    preg_match("/\w+/", $notation, $matches);
    return $matches[0];
  }
  /**
   * [parse_specific_array_notation description]
   * @param  string $notation [description]
   * @return array            [description]
   */
  private function parse_specific_array_notation(string $notation):array
  {
    preg_match("/\w+/", $notation, $matches);
    $key = $matches[0];
    $notation = preg_replace(self::ARRAY_REGEX, '', $notation);
    $notation = str_replace(']', '', $notation);
    return [$key, $notation];
  }
}
