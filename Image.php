<?php

namespace Netflex\Structure;

use Netflex\Structure\File;

/**
 * @property int $file
 * @property string|null $name
 * @property string|null $description
 * @package Netflex\Structure
 */
class Image extends File
{
    public function getFileAttribute($file)
    {
        return $file ? (int) $file : null;
    }

    /**
     * @param string|null $preset 
     * @return string|null 
     */
    public function url($preset = 'default')
    {
        if ($path = $this->getPathAttribute()) {
            if ($preset) {
                return media_url($this->getPathAttribute(), $preset);
            }

            return cdn_url($path);
        }
    }
}
