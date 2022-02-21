<?php

namespace Netflex\Structure;

use Carbon\Carbon;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

use Illuminate\Support\Collection;
use Netflex\Files\File;
use Netflex\Structure\File as StructureFile;
use Netflex\Structure\Contracts\StructureField;
use Netflex\Support\HtmlString;
use Netflex\Support\Accessors;
use Netflex\Structure\Model;

use Netflex\RuleBuilder\DateRules\DateRule;
use Netflex\RuleBuilder\Exceptions\RuleBuilderException;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read string $alias
 * @property-read string $type
 * @property-read string $content_field
 * @property-read int $collection_id
 * @property-read string|null $code
 * @property-read int|null $sorting
 */
class Field implements CastsAttributes
{
  use Accessors;

  protected $type;

  public function __construct($typeOrAttributes = 'string')
  {
    if (is_string($typeOrAttributes)) {
      $this->type = $typeOrAttributes;
      $this->attributes = [];
    } else if (is_array($typeOrAttributes)) {
      $this->type = $typeOrAttributes['type'];
      $this->attributes = $typeOrAttributes;
    }
  }

  public function getIdAttribute($id)
  {
    return (int) $id;
  }

  public function getCollectionIdAttribute($collectionId)
  {
    return (int) $collectionId;
  }

  public function getSortingAttribute($sorting)
  {
    return (int) $sorting;
  }

  /**
   * @param Collection $fields
   * @param Collection $keys
   * @return static
   */
  protected function findField(Collection $fields, Collection $keys)
  {
    $key = $keys->shift();

    $fieldData = $fields->first(function ($field) use ($key) {
      return is_object($field)
        ? $field->alias === $key
        : $field['alias'] == $key;
    });

    $field = new static($fieldData->attributes ?? $fieldData);

    if ($keys->count() === 0) {
      return $field;
    }

    $subFields = $field->type === 'matrix'
      ? $field->blocks
      : $field->fields;

    return $this->findField(Collection::make($subFields), $keys);
  }

  /**
   * @param Model $model
   * @param string|array $key
   * @return static
   */
  protected function getField(Model $model, $key)
  {
    return $this->findField($model->structure->fields, Collection::make($key));
  }

  protected function getBlockField(array $block, Field $field)
  {
    $blockFieldAttributes = Collection::make($field->blocks)
      ->first(function ($blockFieldAttributes) use ($block) {
        return $blockFieldAttributes['alias'] === $block['type'];
      });

    return new static($blockFieldAttributes);
  }

  /**
   * @param Model $model
   * @param string|array $keys
   * @param mixed $value
   * @param array $attributes
   * @return array|bool|Carbon|float|int|mixed|EditorBlocks|JSON|null
   */
  public function get($model, $keys, $value, $attributes)
  {
    switch ($this->type) {
      case 'checkbox':
        return boolval(intval($value));
      case 'customer-group':
      case 'entry':
      case 'customer':
        return $value ? intval($value) : null;
      case 'integer':
        return intval($value);
      case 'float':
        return floatval($value);
      case 'tags':
        return array_values(array_filter(explode(',', $value)));
      case 'file':
        return StructureFile::cast($value);
      case 'image':
        return Image::cast($value);
      case 'editor-small':
      case 'editor-large':
        return $value ? new HtmlString($value) : null;
      case 'multiselect':
        return array_map(function ($value) {
          if (preg_match('/(^\d+(?:\.\d+)?$)/', trim($value))) {
            $value = trim($value);

            if (strpos($value, '.') !== false) {
              return floatval($value);
            }

            return intval($value);
          }

          return $value;
        }, array_values(array_filter(explode(',', $value), function ($value) {
          return is_string($value) ? strlen(trim($value)) : $value !== null;
        })));
      case 'customer-groups':
      case 'entries':
      case 'entriessortable':
      case 'customers':
        return array_map(function ($value) {
          return $value ? intval($value) : null;
        }, array_values(array_filter(explode(',', $value))));
      case 'json':
        return new JSON(json_decode($value, true));
      case 'rule-builder':
        if ($value) {
          try {
            return DateRule::fromJson($value);
          } catch (RuleBuilderException $e) {
            return null;
          }
        }

        return null;
      case 'editor-blocks':
        return new EditorBlocks($value);
      case 'date':
        return $value ? Carbon::parse($value)->startOfDay() : null;
      case 'datetime':
        return $value ? Carbon::parse($value) : null;
      case 'matrix':
        $field = $this->getField($model, $keys);

        return Collection::make($value)
          ->map(function ($block) use ($model, $keys, $value, $field) {
            $blockField = $this->getBlockField($block, $field);

            return $blockField->get($model, [$keys, $block['type']], $block, $blockField->attributes);
          })
          ->toArray();
      case 'matrix_block':
        $field = $this->getField($model, $keys);

        return Collection::make($field->fields)
          ->mapWithKeys(function ($fieldAttributes) use ($model, $value, $keys) {
            $field = new static($fieldAttributes);

            $fieldName = $fieldAttributes['alias'];

            $fieldValue = $value[$fieldName] ?? null;

            return [
              $fieldName => $field->get($model, array_merge($keys, [$fieldName]), $fieldValue, $fieldAttributes),
            ];
          })
          ->merge([
            'type' => is_object($value) ? $value->type : $value['type'],
          ])
          ->map(function ($item) {
            if (!($item instanceof StructureField)) {
              return $item instanceof Arrayable ? $item->toArray() : $item;
            }

            return $item;
          })
          ->all();
      default:
        return $value;
    }
  }

  /**
   * @param Model $model
   * @param string[] $keys
   * @param mixed $value
   * @param mixed[] $attributes
   */
  public function set($model, $keys, $value, $attributes)
  {
    $key = Collection::make($keys)->pop();

    switch ($this->type) {
      case 'checkbox':
        $value = $value ? '1' : '0';
        break;
      case 'file':
      case 'image':
        if ($value instanceof File || $attributes instanceof StructureFile || $attributes instanceof Image) {
          $value = $value->id;
          break;
        }
      case 'integer':
      case 'entry':
      case 'customer':
      case 'float':
        $value = (string) $value;
        break;
      case 'multiselect':
      case 'tags':
      case 'entries':
      case 'entriessortable':
      case 'customers':
        $value = is_array($value) ? implode(',', $value) : $value;
        break;
      case 'editor-small':
      case 'editor-large':
        $value = $value instanceof HtmlString ? $value->__toString() : $value;
        break;
      case 'json':
        $value = $value instanceof JSON ? $value->jsonSerialize() : $value;
        break;
      case 'rule-builder':
        $value = $value instanceof DateRule ? $value->jsonSerialize() : $value;
        break;
      case 'editor-blocks':
        $value = $value instanceof EditorBlocks ? $value->jsonSerialize() : $value;
        break;
      case 'date':
        $value = $value instanceof Carbon ? $value->toDateString() : $value;
        break;
      case 'datetime':
        $value = $value instanceof Carbon ? $value->toDateTimeString() : $value;
        break;
      case 'matrix':
        $field = $this->getField($model, $keys);

        $value = Collection::make($value)
          ->map(function ($block) use ($model, $keys, $value, $field) {
            $blockField = $this->getBlockField($block, $field);

            $blockKey = $block['type'];

            return $blockField->set($model, [$keys, $blockKey], $block, $blockField->attributes)[$blockKey];
          })
          ->toArray();

        break;
      case 'matrix_block':
        $field = $this->getField($model, $keys);

        $value = Collection::make($field->fields)
          ->mapWithKeys(function ($fieldAttributes) use ($model, $value, $keys) {
            $field = new static($fieldAttributes);

            $fieldName = $fieldAttributes['alias'];

            $fieldValue = $value[$fieldName] ?? null;

            $foo = [
              $fieldName => $field->set($model, array_merge($keys, [$fieldName]), $fieldValue, $fieldAttributes)[$fieldName],
            ];

            return $foo;
          })
          ->merge([
            'type' => $value['type'],
          ])
          ->toArray();
        break;
      default:
        break;
    }

    return [$key => $value];
  }

  public function __debugInfo()
  {
    return $this->attributes;
  }
}
