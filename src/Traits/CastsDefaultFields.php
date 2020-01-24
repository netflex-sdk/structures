<?php

namespace Netflex\Structure\Traits;

trait CastsDefaultFields
{
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

    static::retrieved(function ($model) use ($defaults) {
      $model->casts = array_merge($model->casts, $defaults);
    });

    static::created(function ($model) use ($defaults) {
      $model->casts = array_merge($model->casts, $defaults);
    });
  }
}
