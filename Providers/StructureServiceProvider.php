<?php

namespace Netflex\Structure\Providers;


use Illuminate\Support\ServiceProvider;

use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Netflex\Structure\Structure;
use Throwable;

class StructureServiceProvider extends ServiceProvider
{
    public function boot ()
    {
        $models = [];
        $classFinder = fn () => ClassFinder::getClassesInNamespace('App\Models', ClassFinder::RECURSIVE_MODE);

        if(in_array(App::environment(), ['master', 'dev'])) {
          $models = Cache::rememberForever('netflex/structure/models', $classFinder);
        } else {
          $models = $classFinder();
        }

        foreach ($models as $model) {
            try {
              if (!Structure::isModelRegistered($model)) {
                Structure::registerModel($model);
              }
            } catch (Throwable $e) {
                continue;
            }
        }
    }
}
