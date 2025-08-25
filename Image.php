<?php

namespace Netflex\Structure;

/**
 * @property int $file
 * @property string|null $name
 * @property string|null $description
 * @package Netflex\Structure
 */
class Image extends File
{
    public function getFileAttribute($file): int|null
    {
        return $file ? (int) $file : null;
    }

    /**
     * @param string|null $preset
     * @return string|null
     */
    public function url($preset = 'default'): string|null
    {
        if ($path = $this->getPathAttribute()) {
            if ($preset) {
                return media_url($this->getPathAttribute(), $preset);
            }

            return cdn_url($path);
        }

        return null;
    }
}
