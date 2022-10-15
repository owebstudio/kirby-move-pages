# Kirby Move Pages

Missing move pages feature for Kirby 3. You can move the pages under the parent pages you specify with this plugin.

## Installation

1. Download the latest release
2. Unzip downloaded file
3. Copy/paste unzipped folder in your `/site/plugins` folder

## Usage

Don't forget! To move a page under the another page, the parent page must support the page template you are moving. This option must be a query language string or an array of templates and must be a collection of pages, not a single page.

### Templates

Simply specify under which parent templates a page can be moved.

```yaml
# /site/blueprints/pages/project.yml
title: Project

options:
    # parent page templates where you can move this page
    move:
        - projects
        - portfolio
```

### Query language

If you need a more complex logic or dynamic parent templates, you can use the query language.

```yaml
# /site/blueprints/pages/project.yml
title: Project

options:
    # parent pages collection where you can move this page
    move: site.children.filterBy('template', 'in', ['projects', 'portfolio'])
```


### Hooks

Available hooks for this plugin: `page.move:before` and `page.move:after`.

```php
// /site/config/config.php
return [
    'hooks' => [
        'page.move:before' => function (Kirby\Cms\Page $page, ?Kirby\Cms\Page $parent = null) {
            // your code goes here
        },
        'page.move:after' => function (Kirby\Cms\Page $newPage, Kirby\Cms\Page $oldPage, ?Kirby\Cms\Page $parent = null) {
            // your code goes here
        }
    ]
];
```

## Sample use case: shop

For example, let's assume that there is a multi-category structure on a shop site. You want to move a product to another category.

### Content directory

- /home
- /about
- /shop
    - /category-a
        - Product A
    - /category-b
        - Product B
    - /category-c
        - Product C
- /contact

You can set `move` option with `category` template:

```yaml
# /site/blueprints/pages/product.yml
title: Product item

options:
    move:
        - category
```

Or you can use query language:

```yaml
# /site/blueprints/pages/product.yml
title: Product item

options:
    move: kirby.page('shop').children
```
