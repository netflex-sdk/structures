<?php

namespace Netflex\Structure\Traits;

trait HidesDefaultFields
{
  public static function bootHidesDefaultFields()
  {
    static::retrieved(function ($model) {
      if ($model->hidesDefaultFields ?? false === true) {
        $model->hidden = array_merge($model->hidden, $model->defaultFields);
      }
    });

    static::created(function ($model) {
      if ($model->hidesDefaultFields ?? false === true) {
        $model->hidden = array_merge($model->hidden, $model->defaultFields);
      }
    });
  }
}
