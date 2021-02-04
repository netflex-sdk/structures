<?php

namespace Netflex\Structure\Contracts;

/**
 * @property string $path
 * @package Netflex\Structure\Contracts
 */
interface MediaUrlResolvable
{
    /** @return string */
    public function getPathAttribute();
}
