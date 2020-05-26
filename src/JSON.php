<?php

namespace Netflex\Structure;

use Netflex\Support\Accessors;

use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Htmlable;

class JSON implements JsonSerializable, Jsonable, Htmlable
{
  use Accessors;

  protected $attributes = [];

  public function __construct($attributes = [])
  {
    $this->attributes = $attributes ?? [];
    foreach ($this->attributes as $key => $value) {
      $this->attributes[$key] = json_decode(json_encode($value));
    }
  }

  public function toJson($options = 0)
  {
    return json_encode($this->jsonSerialize(), $options);
  }

  public function jsonSerialize()
  {
    return $this->attributes;
  }

  public function toHtml()
  {
    return $this->toJson();
  }
}
