<?php

namespace Netflex\Structure;

use Netflex\Structure\Structure;

class Entry extends Model
{
    /**
     * @return Closure
     */
    protected function getMapper()
    {
        return function ($attributes) {
            if (isset($attributes['directory_id'])) {
                $relationId = $attributes['directory_id'];

                return Structure::resolveModel($relationId)
                    ->newFromBuilder($attributes);
            }

            return (new static)->newFromBuilder($attributes);
        };
    }

    /**
     * @return Structure|null
     */
    public function getStructureAttribute()
    {
        return Structure::retrieve($this->directory_id);
    }
}
