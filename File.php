<?php

namespace Netflex\Structure;

use ArrayAccess;
use JsonSerializable;

use Netflex\Support\Accessors;
use Netflex\Pages\Contracts\MediaUrlResolvable;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Netflex\Structure\Contracts\StructureField;

class File implements ArrayAccess, MediaUrlResolvable, JsonSerializable, Arrayable, Jsonable, StructureField
{
    use Accessors;

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes ?? [];
    }

    /**
     * @param array|null $attributes 
     * @return static|null 
     */
    public static function cast($attributes = [])
    {
        if ($attributes && is_array($attributes) && array_key_exists('path', $attributes) && $attributes['path']) {
            return new static($attributes);
        }

        return null;
    }

    /**
     * @param array $attributes
     * @return static
     */
    public function newFromBuilder($attributes = [])
    {
        return new static($attributes);
    }

    public function raw()
    {
        return $this->attributes;
    }

    public function getPathAttribute()
    {
        return $this->attributes['path'] ?? null;
    }

    /**
     * @param null $preset Not used
     * @return string|null 
     */
    public function url($preset = null)
    {
        if ($path = $this->getPathAttribute()) {
            return cdn_url($path);
        }
    }

    #[\ReturnTypeWillChange]
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
