<?php

namespace Netflex\Structure;

use ArrayAccess;

use Netflex\Support\Accessors;
use Netflex\Pages\Contracts\MediaUrlResolvable;

use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;

class File implements ArrayAccess, MediaUrlResolvable, JsonSerializable, Jsonable
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

    public function __debugInfo()
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            $attributes[$key] = $this->__get($key);
        }

        return $attributes;
    }

    public function jsonSerialize()
    {
        return $this->attributes;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
