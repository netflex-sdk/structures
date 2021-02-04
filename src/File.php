<?php

namespace Netflex\Structure;

use ArrayAccess;

use Netflex\Support\Accessors;
use Netflex\Pages\Contracts\MediaUrlResolvable;

class File implements ArrayAccess, MediaUrlResolvable
{
    use Accessors;

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes ?? [];
    }

    public function getPathAttribute()
    {
        return $this->attributes['path'];
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
