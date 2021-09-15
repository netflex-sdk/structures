<?php

namespace Netflex\Structure;

use Exception;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

use Netflex\API\Contracts\APIClient;
use Netflex\API\Facades\API;
use Netflex\Structure\Model;

use Netflex\Support\Accessors;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read string|null $value_single
 * @property-read string|null $value_edit
 * @property-read string|null $value_create
 * @property-read string|null $value_media
 * @property-read string|null $value_relations
 * @property-read string|null $value_name
 * @property-read string|null $image_field
 * @property-read string|null $value_tags
 * @property-read string|null $value_variants
 * @property-read string|null $value_author
 * @property-read bool $hide_url
 * @property-read mixed $generate_sitemap
 * @property-read string|null $image
 * @property-read string|null $icon
 * @property-read bool $published
 * @property-read string $created
 * @property-read int $userid
 * @property-read mixed $config
 * @property-read Collection $fields
 */
class Structure
{
  use Accessors;

  protected $attributes = [];

  /**
   * @param array $attributes 
   */
  protected function __construct(array $attributes = [])
  {
    $this->attributes = $attributes;
  }

  /**
   * @param string $model
   * @return bool
   */
  public static function isModelRegistered(string $model)
  {
    $instance = new $model([], false);
    
    if ($instance instanceof Model) {
      return App::bound('structure.' . $instance->getRelationId());
    }

    return false;
  }

  /**
   * Register a model
   * @param string $model 
   * @return bool
   */
  public static function registerModel(string $model, bool $overwrite = true)
  {
    $instance = new $model([], false);

    if ($instance instanceof Model) {
      if (!$overwrite) {
        if (App::bound('structure.' . $instance->getRelationId())) {
          return false;
        }
      }

      App::bind('structure.' . $instance->getRelationId(), $model);

      return true;
    }

    return false;
  }

  /**
   * @param mixed $id 
   * @return Model
   * @throws Exception
   */
  public static function resolveModel($id)
  {
    try {
      return App::make('structure.' . $id);
    } catch (BindingResolutionException $e) {
      return App::make(Entry::class);
    }
  }

  /**
   * Gets the registered model for this structure
   * @return string
   */
  public function model()
  {
    return static::resolveModel($this->id);
  }

  /**
   * @param int $id 
   * @param APIClient|null $client
   * @return static|null 
   */
  public static function retrieve($id, $client = null)
  {
    $client = $client ?? API::connection();
    $prefix = $client->getConnectionName();
    $prefix = $prefix !== 'default' ? $prefix : null;
    $key = implode('/', array_filter([$prefix, 'structures', $id]));

    try {
      return Cache::rememberForever($key, function () use ($id, $client) {
        return new static($client->get("builder/structures/$id/basic", true));
      });
    } catch (Exception $e) {
      return null;
    }
  }

  public function getIdAttribute($id)
  {
    return (int) $id;
  }

  public function getPublishedAttribute($published)
  {
    return (bool) $published;
  }

  public function getFieldsAttribute($fields = [])
  {
    return Collection::make($fields)->map(function ($field) {
      return new Field($field);
    });
  }
}
