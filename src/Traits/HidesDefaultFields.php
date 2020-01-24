<?php

namespace Netflex\Structure\Traits;

trait HidesDefaultFields
{
  public static function bootHidesDefaultFields()
  {
    $defaults = [
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

    static::retrieved(function ($model) use ($defaults) {
      $model->hidden = array_merge($model->hidden, $defaults);
    });

    static::created(function ($model) use ($defaults) {
      $model->hidden = array_merge($model->hidden, $defaults);
    });
  }
}
