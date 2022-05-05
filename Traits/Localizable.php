<?php

namespace Netflex\Structure\Traits;

use ArrayAccess;
use Illuminate\Support\Facades\App;
use Netflex\Query\QueryableModel;
use Netflex\Structure\Model;
use Netflex\Support\ReactiveObject;

trait Localizable
{
    protected $forcedLocale = null;

    protected $reservedKeys = [
        'name',
    ];

    protected function setLocale($locale)
    {
        $this->forcedLocale = $locale;
    }

    protected function getLocale()
    {
        if ($this->forcedLocale) {
            return $this->forcedLocale;
        }

        return App::getLocale();
    }

    protected function isKeyReserved($key)
    {
        if (property_exists($this, 'isLocalizedArray') && $this->isLocalizedArray) {
            return false;
        }

        return in_array($key, $this->reservedKeys);
    }

    protected function getLocalizedKeys($key)
    {
        $locale = $this->getLocale();
        $parts = explode('_', $locale);
        $keys = [];

        if (count($parts)) {
            $lang = $parts[0];
            $keys = [$key . '_' . $locale, $key . '_' . $lang];
        }

        $fallbackLocale = App::getFallbackLocale();

        if ($fallbackLocale !== $locale && !$this->isKeyReserved($key)) {
            if (count($parts)) {
                $parts = explode('_', $fallbackLocale);
                $lang = $parts[0];
                return array_merge($keys, [$key . '_' . $fallbackLocale, $key . '_' . $lang, $key]);
            }
        }

        return array_merge($keys, [$key]);
    }

    protected function getLocalizedArray($array)
    {
        if (is_array($array)) {
            $model = new class() extends Model implements ArrayAccess
            {
                use Localizable;
                protected $isLocalizedArray = true;
                /**
                 * @return null
                 */
                public function getStructureAttribute()
                {
                    return null;
                }
            };

            return $model->newFromBuilder($array);
        }

        return $array;
    }

    public function getAttribute($key)
    {
        $getAttribute = function ($key) {
            if ($this instanceof QueryableModel) {
                return parent::getAttribute($key);
            }

            if ($this instanceof ReactiveObject) {
                return parent::__get($key);
            }

            return $this->{$key};
        };


        foreach ($this->getLocalizedKeys($key) as $attribute) {
            if ($property = $getAttribute($attribute)) {
                if (is_array($property)) {
                    return array_map(fn ($item) => $this->getLocalizedArray($item), $property);
                }
                return $property;
            }
        }

        $property = $getAttribute($attribute);

        if (is_array($property)) {
            return array_map(fn ($item) => $this->getLocalizedArray($item), $property);
        }

        return $property;
    }

    public function __get($key)
    {
        return $this->getAttribute($key);
    }
}
