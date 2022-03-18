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
        return once(function () {
            return function ($attributes) {
                if (isset($attributes['directory_id'])) {
                    $relationId = $attributes['directory_id'];

                    return Structure::resolveModel($relationId)
                        ->newFromBuilder($attributes);
                }

                return (new static)->newFromBuilder($attributes);
            };
        });
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
        return once(fn () => Structure::retrieve($this->directory_id));
    }

    /**
     * Mass import entries
     *
     * @param array|Collection $entries
     * @param string|null $notify Email to notify when the entries are imported
     * @return bool
     */
    public static function import($entries, $notify = null)
    {
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

            if ($notify) {
                $payload['notify_mail'] = $notify;
            }

            $client->post('/api/v1/structure/' . $relationId . '/entries/import', $payload);
        }

        return true;
    }
}
