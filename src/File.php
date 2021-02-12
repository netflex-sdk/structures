<?php

namespace Netflex\Structure;

use ArrayAccess;
use JsonSerializable;

use Netflex\Support\Accessors;
use Netflex\Pages\Contracts\MediaUrlResolvable;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class File implements ArrayAccess, MediaUrlResolvable, JsonSerializable, Arrayable, Jsonable
{
    use Accessors;

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes ?? [];
    }

    public function getPathAttribute()
    {
        return $this->attributes['path'] ?? null;  
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function __debugInfo()
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            $attributes[$key] = $this->__get($key);
        }

        return $attributes;
    }
}
