<?php

namespace Netflex\Structure;


use Netflex\Support\Accessors;
use Netflex\Pages\MediaPreset;
use Netflex\Structure\Contracts\StructureField;

use Illuminate\Support\Facades\Config;

use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @property int $time
 * @property array $blocks
 * @property string $version
 * @property string|null $author
 */
class EditorBlocks implements JsonSerializable, Jsonable, Htmlable, StructureField
{
  use Accessors;

  protected $attributes = [];

  public function __construct($attributes = [])
  {
    $this->attributes = $attributes ?? [];
  }

  public function raw()
  {
    return $this->attributes;
  }

  public function getBlocksAttribute($blocks = [])
  {
    return collect($blocks)->map(function ($block) {
      return json_decode(json_encode($block));
    });
  }

  #[\ReturnTypeWillChange]
  public function jsonSerialize()
  {
    return count($this->attributes) ? $this->attributes : null;
  }

  public function toJson($options = 0)
  {
    return json_encode($this->jsonSerialize(), $options);
  }

  /**
   * Get content as a string of HTML.
   *
   * @return string
   */
  public function toHtml()
  {
    return '<div>' . $this->blocks->map(function ($block) {
      switch ($block->type) {
        case 'header':
          $tag = 'h' . $block->data->level ?? 1;
          return "<$tag>{$block->data->text}</$tag>";
        case 'netflex-image':
          $preset = new MediaPreset(Config::get('media.presets.default') ?? null);
          $src = media_url($block->data->path, $preset->size, $preset->mode, $preset->fill);
          return "<img src=\"$src\">";
        case 'paragraph':
          return "<p>{$block->data->text}</p>";
        case 'quote':
          $quote = $block->data->text;
          $caption = $block->data->caption;
          $caption = $caption ? "<footer>$caption</footer>" : null;
          return "<blockquote>{$quote}{$caption}</blockquote>";
        case 'list':
          $tag = $block->data->style === 'ordered' ? 'ol' : 'ul';
          $items = implode("\n", array_map(function ($item) {
            return "<li>$item</li>";
          }, $block->data->items));
          return "<$tag>$items</$tag>";
        case 'linkTool':
          $href = $block->data->link ?? null;
          $title = $block->data->meta->title ?? $href;
          return "<a href=\"$href\">$title</a>";
        case 'embed':
          $src = $block->data->embed ?? null;
          $width = $block->data->embed->height ?? null;
          $width = $width ? "width=\"$width\"" : null;
          $height = $block->data->embed->height ?? null;
          $height = $height ? "height=\"$height\"" : null;
          return "<iframe $width $height src=\"$src\"></iframe>";
        default:
          return $block->data->text ?? null;
      }
    })->join("\n") . '</div>';
  }
}
