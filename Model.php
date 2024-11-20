<?php

namespace Netflex\Structure;

use Exception;
use Throwable;

use Apility\SEOTools\Facades\SEOTools;

use Netflex\Query\QueryableModel;
use Netflex\Query\Exceptions\NotFoundException;

use Netflex\Structure\Traits\CastsDefaultFields;
use Netflex\Structure\Traits\HidesDefaultFields;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Netflex\Signups\Signup;

/**
 * @property int $id
 * @property int $directory_id
 * @property string $name
 * @property string|null $title
 * @property string $url
 * @property int $revision
 * @property Carbon $created
 * @property Carbon $updated
 * @property bool $published
 * @property string|null $author
 * @property int $userid
 * @property bool $use_time
 * @property Carbon|null $start
 * @property Carbon|null $stop
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
   * The connection name for the model.
   *
   * @var string|null
   */
  protected $connection = 'default';

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
   * Determines if QueryableModel::all() calls in queries should chunk the result.
   * NOTICE: If chunking is enabled, the results of QueryableModel::all() will not be cached, and can result in a performance hit on large structures.
   *
   * @var bool
   */
  protected $useChunking = false;

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
   * If an accessor method exists, determines if the cast or accessor should run.
   *
   * @var bool
   */
  protected $castIfAccessorExists = false;

  /**
   * Indicates if we should automatically apply SEO tags when resolving model by URL
   *
   * @var bool
   */
  protected $applySEO = true;

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
      $this->attributes = $this->getConnection()
        ->get("builder/structures/entry/{$this->attributes['id']}/revision/{$revisionId}", true);
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
  protected function performRetrieveRequest(?int $relationId = null, mixed $key = null)
  {
    return $this->getConnection()
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
    $response = $this->getConnection()
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
  protected function performUpdateRequest(?int $relationId = null, mixed $key = null, array $attributes = [])
  {
    return $this->getConnection()
      ->put('builder/structures/entry/' . $key, $attributes);
  }

  /**
   * Deletes a record
   *
   * @param int|null $relationId
   * @param mixed $key
   * @return bool
   */
  protected function performDeleteRequest(?int $relationId = null, mixed $key = null)
  {
    try {
      $this->getConnection()
        ->delete('builder/structures/entry/' . $key);
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
    return Structure::retrieve($this->relationId, $this->getConnection());
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
      return static::resolvedRouteBinding(static::find($rawValue));
    }

    $query = static::where($field, $rawValue);

    if ($field === 'url') {
      $query = $query->orWhere($field, $rawValue . '/');
    }

    /** @var static */
    if ($model = $query->first()) {
      if ($field !== 'url') {
        if ($model->{$field} != $rawValue) {
          throw new NotFoundException;
        }
      }

      return static::resolvedRouteBinding($model);
    }

    throw new NotFoundException;
  }

  protected static function resolvedRouteBinding(?Model $model = null)
  {
    if ($model && $model->applySEO) {
      if ($title = $model->name) {
        SEOTools::opengraph()->setTitle($title);
        SEOTools::twitter()->setTitle($title);
        SEOTools::jsonLd()->setTitle($title);
        SEOTools::metatags()->setTitle($title);
      }
    }

    return $model;
  }

  /**
   * Register the model
   * @return bool
   * @throws Exception 
   */
  public static function register()
  {
    return Structure::registerModel(static::class);
  }

  /**
   * Mass import entries synchronously
   *
   * @param array|Collection $entries
   * @param  array|string|null $config Config array, or notify email, or notify url
   * @return bool
   */
  public static function importSync($entries, $config = [])
  {
    $instance = new static;

    if (is_string($config)) {
      if (filter_var($config, FILTER_VALIDATE_EMAIL)) {
        $config = ['notify_mail' => $config];
      }

      if (filter_var($config, FILTER_VALIDATE_URL)) {
        $config = ['webhook' => $config];
      }
    }

    if (!is_array($config)) {
      $config = [];
    }

    if (method_exists($instance, 'getConnectionName') && $instance->getConnectionName() !== 'default') {
      $prefix = $instance->getConnectionName();
      $config['prefix'] = $prefix;
    }

    $config['sync'] = true;

    return static::import($entries, $config);
  }

  /**
   * Mass import entries
   *
   * @param array|Collection $entries
   * @param  array|string|null $config Config array, or notify email, or notify url
   * @return bool
   */
  public static function import($entries, $config = [])
  {
    $instance = new static;
    $client = $instance->getConnection();

    if (is_string($config)) {
      if (filter_var($config, FILTER_VALIDATE_EMAIL)) {
        $config = ['notify_mail' => $config];
      }

      if (filter_var($config, FILTER_VALIDATE_URL)) {
        $config = ['webhook' => $config];
      }
    }

    if (!is_array($config)) {
      $config = [];
    }

    if (!($entries instanceof Collection)) {
      $entries = collect($entries);
    }

    $payload = [
      'entries' => $entries->map(function ($entry) use ($instance) {
        if (!is_array($entry)) {
          $entry = $entry->toArray();
        }
        $entry['directory_id'] = $instance->relationId;
        $entry['revision_publish'] = true;
        if (!isset($entry['name']) && !isset($entry['id'])) {
          $entry['name'] =  Str::uuid();
        }
        return $entry;
      })->toArray(),
    ];

    foreach ($config as $key => $value) {
      if ($key !== 'entries') {
        $payload[$key] = $value;
      }
    }

    $client->post('builder/structures/' . $instance->relationId . '/import', $payload);

    return true;
  }

  public function getSignupsAttribute()
  {
    return Signup::forEntry($this);
  }

  public function createSignup(array $payload = []): Signup
  {
    return Signup::createForEntry($this, $payload);
  }
}
