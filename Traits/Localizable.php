<?php

namespace Netflex\Structure\Traits;

use ArrayAccess;
use Illuminate\Support\Facades\App;
use Netflex\Structure\Model;

trait Localizable
{
    protected $reservedKeys = [
        'name',
    ];

    protected function isKeyReserved($key)
    {
        if (property_exists($this, 'isLocalizedArray') && $this->isLocalizedArray) {
            return !in_array($key, $this->reservedKeys);
        }

        return true;
    }

    protected function getLocalizedKeys($key)
    {
        $locale = App::getLocale();
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

    public function getAttribute($key)
    {
        foreach ($this->getLocalizedKeys($key) as $attribute) {
            if ($property = parent::getAttribute($attribute)) {
                if (is_array($property)) {
                    return array_map(fn ($item) => $this->getLocalizedArray($item), $property);
                }
                return $property;
            }
        }

        $property = parent::getAttribute($attribute);

        if (is_array($property)) {
            return array_map(fn ($item) => $this->getLocalizedArray($item), $property);
        }

        return $property;
    }

    protected function getLocalizedArray($array)
    {
        if (is_array($array)) {
            $model = new class() extends Model implements ArrayAccess
            {
                use Localizable;
                protected $isLocalizedArray = true;
            };

            return $model->newFromBuilder($array);
        }

        return $array;
    }

    protected function jsonSerializeLocalized($array)
    {
        $keys = collect($array)->keys();
        $localizableKeys = $keys->filter(function ($key) {
            return strpos($key, '_' . App::getLocale()) !== false;
        });

        $actualKeys = $keys->diff($localizableKeys);

        $keys = [];
        $json = [];

        foreach ($actualKeys as $key) {
            $localizedKey = $key . '_' . App::getLocale();
            if ($localizableKeys->contains($localizedKey) && $array[$localizedKey] ?? null) {
                $json[$key] = $array[$localizedKey];
            } else {
                $json[$key] = $array[$key];
            }

            if (is_array($json[$key])) {
                $json[$key] = $this->localize($json[$key]);
            }
        }

        return $json;
    }

    public function jsonSerialize()
    {
        return $this->jsonSerializeLocalized(parent::jsonSerialize());
    }
}
