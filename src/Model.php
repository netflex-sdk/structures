<?php

namespace Netflex\Structure;

use Throwable;

use Netflex\API\Facades\API;
use Netflex\Query\QueryableModel;

use Netflex\Query\Exceptions\NotFoundException;

use Netflex\Structure\Traits\CastsDefaultFields;
use Netflex\Structure\Traits\HidesDefaultFields;

/**
 * @property int $id
 * @property int $directory_id
 * @property string $name
 * @property string|null $title
 * @property string $url
 * @property int $revision
 * @property string $created
 * @property string $updated
 * @property bool $published
 * @property string|null $author
 * @property int $userid
 * @property bool $use_time
 * @property string|null $start
 * @property string|null $stop
 * @property array $tags
 * @property bool $public
 * @property mixed $authgroups
 * @property array $variants
 * @property-read Structure|null $structure
 */
abstract class Model extends QueryableModel
{
  use CastsDefaultFields;
  use HidesDefaultFields;

  /**
   * The relation associated with the model.
   *
   * @var string
   */
  protected $relation = 'entry';

  /**
   * The directory_id associated with the model.
   *
   * @var int
   */
  protected $relationId;

  /**
   * The resolvable field associated with the model.
   *
   * @var string
   */
  protected $resolvableField = 'url';

  /**
   * Indicates if we should automatically publish the model on save.
   *
   * @var bool
   */
  protected $autoPublishes = true;

  /**
   * Indicates if we should respect the models publishing status when retrieving it.
   *
   * @var bool
   */
  protected $respectPublishingStatus = true;

  /**
   * The number of models to return for pagination. Also determines chunk size for LazyCollection
   *
   * @var int
   */
  protected $perPage = 100;

  /**
   * Indicates if the model should hide default fields
   *
   * @var bool
   */
  protected $hidesDefaultFields = true;

  /**
   * Indicates if the model should automatically resolve and cast the fields to the correct types
   *
   * @var bool
   */
  protected $castsCustomFields = true;

  /**
   * Indicates which fields are considered default fields
   *
   * @var string[]
   */
  protected $defaultFields = [
    'directory_id',
    'title',
    'revision',
    'published',
    'userid',
    'use_time',
    'start',
    'stop',
    'tags',
    'public',
    'authgroups',
    'variants',
  ];

  /**
   * Indicates if the model should be timestamped.
   *
   * @var bool
   */
  public $timestamps = true;

  /**
   * The attributes that should be mutated to dates.
   *
   * @var array
   */
  protected $dates = [
    'created',
    'updated',
    'start',
    'stop'
  ];

  /**
   * The attributes that should be hidden for arrays.
   *
   * @var array
   */
  protected $hidden = [];

  /**
   * Determines if we should cache some results.
   *
   * @var bool
   */
  protected $cachesResults = true;

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
    return !!API::delete('builder/structures/entry/' . $key);
  }

  /**
   * @return Structure|null
   */
  public function getStructureAttribute()
  {
    return Structure::retrieve($this->relationId);
  }

  /**
   * Retrieve the model for a bound value.
   *
   * @param  mixed  $rawValue
   * @param  string|null $field
   * @return \Illuminate\Database\Eloquent\Model|null
   * @throws NotFoundException
   */
  public function resolveRouteBinding($rawValue, $field = null)
  {
    $field = $field ?? $this->getResolvableField();

    if ($field === 'id') {
      return static::find($rawValue);
    }

    $query = static::where($field, $rawValue);

    if ($field === 'url') {
      $query = $query->orWhere($field, $rawValue . '/');
    }
      
    /** @var static */
    if ($model = $query->first()) {
      return $model;
    }

    throw new NotFoundException;
  }
}
