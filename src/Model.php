<?php

namespace Netflex\Structure;

use Netflex\API;

use Netflex\Structure\Traits\HidesDefaultFields;
use Netflex\Query\QueryableModel as Adapter;

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
 */
abstract class Model extends Adapter
{
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
  protected $perPage = 15;

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
   * Retrieves a record by key
   *
   * @param int|null $relationId
   * @param mixed $key
   * @return array|null
   */
  protected function performRetrieveRequest(?int $relationId = null, $key)
  {
    return API::getClient()
      ->get('builder/structures/entry/' . $key, true);
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
    $response = API::getClient()
      ->post('builder/structures/' . $relationId . '/entry', $attributes);

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
    return API::getClient()->put('builder/structures/entry/' . $key, $attributes);
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
    return false;
  }
}
