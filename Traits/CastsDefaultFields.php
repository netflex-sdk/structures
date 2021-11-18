<?php

namespace Netflex\Structure\Traits;

use Netflex\Structure\Model;
use Netflex\Structure\Field;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

trait CastsDefaultFields
{
  /**
   * @return array
   */
  public static function customFieldCasts(Model $model)
  {
    if ($model->castsCustomFields) {
      $prefix = $model->getConnectionName();
      $prefix = $prefix !== 'default' ? $prefix : null;
      $key = implode('/', array_filter([$prefix, static::class . '._fields']));

      $structure = Cache::rememberForever($key, function () use ($model) {
        return $model->structure;
      });


      if ($structure) {
        return $structure->fields->mapWithKeys(function (Field $field) use ($model) {
          $method = Str::camel(implode('_', ['get', $field->alias, 'attribute']));
          $accessor = method_exists($model, $method);

          if (isset($model->castIfAccessorExists) && $model->castIfAccessorExists) {
            $accessor = false;
          }

          return [$field->alias => !$accessor ? (Field::class . ':' . $field->type) : null];
        })
          ->filter()
          ->toArray();
      }
    }

    return [];
  }

  public static function bootCastsDefaultFields()
  {
    $defaults = [
      'id' => 'int',
      'directory_id' => 'int',
      'revision' => 'int',
      'published' => 'bool',
      'userid' => 'int',
      'use_time' => 'bool',
      'start' => 'date',
      'stop' => 'date',
      'public' => 'bool'
    ];

    static::retrieved(function (Model $model) use ($defaults) {
      $defaults = array_merge($defaults, static::customFieldCasts($model));
      $model->casts = array_merge($defaults, $model->casts);
    });

    static::created(function (Model $model) use ($defaults) {
      $defaults = array_merge($defaults, static::customFieldCasts($model));
      $model->casts = array_merge($defaults, $model->casts);
    });
  }
}
