# Netflex Structure

Eloquent compatible model for working with Netflex structures.

<a href="https://packagist.org/packages/netflex/structure"><img src="https://img.shields.io/packagist/v/netflex/structure?label=stable" alt="Stable version"></a>
<a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/github/license/netflex-sdk/structure.svg" alt="License: MIT"></a>
<a href="https://packagist.org/packages/netflex/structure/stats"><img src="https://img.shields.io/packagist/dm/netflex/structure" alt="Downloads"></a>

## Installation

```bash
composer require netflex/structure
```

## Example usage

```php
<?php

use Netflex\Structure\Model;

/**
 * @property string $permalink
 */
class Article extends Model
{
  /**
   * The directory_id associated with the model.
   *
   * @var int
   */
  protected $relationId = 10000;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'name', 'author', 'content'
  ];

  /**
   * Gets the full URL to the article
   *
   * @return string
   */
  public function getPermalinkAttribute()
  {
    return 'https://news.example.com/' . $this->created->format('Y-m-d') - '/' . $this->url;
  }
}

$articlesByJohn = Article::where('author', 'John Doe')
  ->paginate();

$slug = 'top-10-tricks-for-working-with-netflex';
$articleForUrl = Article::resolve($slug);

$firstArticle = Article::first();
$lastArticle = Article::last();

$newestArticle = Article::orderBy('updated', 'desc')->first();

$freshArticle = new Article([
  'name' => 'Fresh new article',
  'author' => 'John Doe',
  'content' => '<h1>Hello world!</h1>'
]);

$freshArticle->save();
```

## Contributing

Thank you for considering contributing to the Netflex Structure! Please read the [contribution guide](CONTRIBUTING.md).

## Code of Conduct

In order to ensure that the community is welcoming to all, please review and abide by the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

Netflex Structure is open-sourced software licensed under the [MIT license](LICENSE.md).

<hr>

Copyright &copy; 2020 **[Apility AS](https://apility.no)**
