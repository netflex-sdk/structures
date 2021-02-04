<?php

namespace Netflex\Structure;

use Throwable;

use Netflex\API\Facades\API;
use Netflex\Query\Builder;

class Entry extends Model
{
    /**
     * @return Closure
     */
    protected function getMapper()
    {
        return function ($attributes) {
            if (isset($attributes['directory_id'])) {
                if ($model = Structure::resolve($attributes['directory_id'])) {
                    return (new $model)->newFromBuilder($attributes);
                }
            }

            return (new static)->newFromBuilder($attributes);
        };
    }

    /**
     * @param Closure[]
     * @return Builder
     * @throws NotQueryableException If object not queryable
     */
    protected static function makeQueryBuilder($appends = [])
    {
        /** @var QueryableModel */
        $queryable = (new static);

        $respectPublishingStatus = $queryable->respectPublishingStatus();
        $relation = $queryable->getRelation();
        $defaultOrderByField = $queryable->defaultOrderByField;
        $defaultSortDirection = $queryable->defaultSortDirection;
        $size = $queryable->perPage ?? null;

        $mapper = $queryable->getMapper();

        $builder = (new Builder($respectPublishingStatus, null, $mapper, $appends))
            ->relation($relation)
            ->assoc(true);

        if ($size) {
            $minSize = Builder::MIN_QUERY_SIZE;
            $maxSize = Builder::MAX_QUERY_SIZE;

            $size = $size < 0 ? ($maxSize + ($size + 1)) : $size;
            $size = $size > $maxSize ? $maxSize : $size;
            $size = $size < $minSize ? $minSize : $size;

            $builder->limit($size);
        }

        if ($defaultOrderByField) {
            $builder->orderBy($defaultOrderByField);
        }

        if ($defaultSortDirection) {
            $builder->orderDirection($defaultSortDirection);
        }

        return $builder;
    }

    /**
     * Loads the given revision
     *
     * @param int $revisionId
     * @return static
     */
    public function loadRevision($revisionId = null)
    {
        if (!$revisionId || $this->revision === (int) $revisionId) {
            return $this;
        }

        try {
            $this->attributes = API::get("builder/structures/entry/{$this->getKey()}/revision/{$revisionId}", true);
            return $this;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Retrieves a record by key
     *
     * @param int|null $relationId
     * @param mixed $key
     * @return array|null
     */
    protected function performRetrieveRequest(?int $relationId = null, $key)
    {
        return API::get('builder/structures/entry/' . $key, true);
    }

    /**
     * Inserts a new record, and returns its id
     *
     * @property int|null $relationId
     * @property array $attributes
     * @return mixed
     */
    protected function performInsertRequest(?int $relationId = null, array $attributes = [])
    {
        $response = API::post('builder/structures/' . $relationId . '/entry', $attributes);

        return $response->entry_id;
    }

    /**
     * Updates a record
     *
     * @param int|null $relationId
     * @param mixed $key
     * @param array $attributes
     * @return void
     */
    protected function performUpdateRequest(?int $relationId = null, $key, $attributes = [])
    {
        return API::put('builder/structures/entry/' . $key, $attributes);
    }

    /**
     * Deletes a record
     *
     * @param int|null $relationId
     * @param mixed $key
     * @return bool
     */
    protected function performDeleteRequest(?int $relationId = null, $key)
    {
        try {
            API::delete('builder/structures/entry/' . $key);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @return Structure|null
     */
    public function getStructureAttribute()
    {
        return Structure::retrieve($this->directory_id);
    }
}
