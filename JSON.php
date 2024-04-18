<?php

namespace Netflex\Structure;

use Netflex\Support\Accessors;

use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Htmlable;
use Netflex\Structure\Contracts\StructureField;

class JSON implements JsonSerializable, Jsonable, Htmlable, StructureField
{
  use Accessors;

  protected $attributes = [];
  protected $original = null;

  public function __construct($attributes = [])
  {
    $this->original = $attributes;
    $this->attributes = $attributes ?? [];
    foreach ($this->attributes as $key => $value) {
      $this->attributes[$key] = json_decode(json_encode($value));
    }
  }

  public function raw()
  {
    return $this->original;
  }

  public function toJson($options = 0)
  {
    return json_encode($this->jsonSerialize(), $options);
  }

  #[\ReturnTypeWillChange]
  public function jsonSerialize()
  {
    return $this->attributes;
  }

  public function toHtml()
  {
    return $this->toJson();
  }
}
