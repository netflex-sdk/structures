<?php

namespace Netflex\Structure\Traits;

use ArrayAccess;
use Illuminate\Support\Facades\App;
use Netflex\Structure\Model;

trait Localizable
{
    protected function getLocalizedKeys($key)
    {
        $locale = App::getLocale();
        $parts = explode('_', $locale);
        $lang = $parts[0];
        return [$key . '_' . $locale, $key . '_' . $lang, $key];
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

    protected function getLocalizedArray(array $array)
    {
        $model = new class() extends Model implements ArrayAccess
        {
            use Localizable;
        };

        return $model->newFromBuilder($array);
    }
}
