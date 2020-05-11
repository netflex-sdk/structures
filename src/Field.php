<?php

namespace Netflex\Structure;

use Netflex\Support\Accessors;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read string $alias
 * @property-read string $type
 * @property-read string $content_field
 * @property-read int $collection_id
 * @property-read string|null $code
 * @property-read int|null $sorting
 */
class Field
{
  use Accessors;

  protected $attributes = [];

  public function __construct(array $attributes = [])
  {
    $this->attributes = $attributes;
  }

  public function getIdAttribute($id)
  {
    return (int) $id;
  }

  public function getCollectionIdAttribute($collectionId)
  {
    return (int) $collectionId;
  }

  public function getSortingAttribute($sorting)
  {
    return (int) $sorting;
  }
}
