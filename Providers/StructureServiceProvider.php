<?php

namespace Netflex\Structure\Providers;


use Illuminate\Support\ServiceProvider;

use HaydenPierce\ClassFinder\ClassFinder;
use Netflex\Structure\Structure;
use Throwable;

class StructureServiceProvider extends ServiceProvider
{
    public function boot ()
    {
        $models = ClassFinder::getClassesInNamespace('App\Models', ClassFinder::RECURSIVE_MODE);
        
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