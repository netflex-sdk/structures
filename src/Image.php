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
}
