<?php

namespace Netflex\Structure;

use Netflex\Structure\Structure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Entry extends Model
{
    /**
     * @return Closure
     */
    protected function getMapper()
    {
        return function ($attributes) {
            if (isset($attributes['directory_id'])) {
                $relationId = $attributes['directory_id'];

                return Structure::resolveModel($relationId)
                    ->newFromBuilder($attributes);
            }

            return (new static)->newFromBuilder($attributes);
        };
    }

    public function usesChunking()
    {
        return $this->useChunking ?? false;
    }

    /**
     * @return Structure|null
     */
    public function getStructureAttribute()
    {
        return Structure::retrieve($this->directory_id);
    }

    /**
     * Mass import entries
     *
     * @param array|Collection $entries
     * @param array[string|null $config
     * @return bool
     */
    public static function import($entries, $config = null)
    {
        $payload = is_array($config) ? $config : [];

        if (is_string($config)) {
            $payload['notify_mail'] = $config;
            $config = [];
        }

        $instance = new static;
        $client = $instance->getConnection();

        if (!($entries instanceof Collection)) {
            $entries = collect($entries);
        }

        $entries = $entries->map(function ($entry) use ($instance) {
            if (!is_array($entry)) {
                $entry = $entry->toArray();
            }
            $entry['directory_id'] = $instance->relationId;
            $entry['revision_publish'] = true;
            if (!isset($entry['name'])) {
                $entry['name'] =  Str::uuid();
            }
            return $entry;
        })->groupBy('directory_id');

        foreach ($entries as $relationId => $entries) {
            $payload = [
                'entries' => $entries->toArray(),
            ];

            $client->post('/api/v1/structure/' . $relationId . '/entries/import', $payload);
        }

        return true;
    }
}
