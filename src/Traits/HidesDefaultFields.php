<?php

namespace Netflex\Structure\Traits;

trait HidesDefaultFields
{
  public static function bootHidesDefaultFields()
  {
    static::booting(function ($model) {
      $model->hidden = array_merge($model->hidden, [
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
        'variants'
      ]);
    });
  }
}
