# Kirby Move Pages

> [!IMPORTANT]
> This plugin is in maintenance mode and only gets security fixes. It supports Kirby 3.6 to 3.10. Kirby 4 and newer can move pages out of the box, so you don't need this plugin anymore. Remove it before you upgrade to Kirby 4, see [Upgrading to Kirby 4](#upgrading-to-kirby-4).

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

### Permission

```yaml
# /site/blueprints/users/editor.yml
title: Editor
permissions:
  owebstudio.move-pages:
    move: false
```

Moving a page changes its URL, so the user also needs the `changeSlug` permission for the page (the `pages.changeSlug` role permission and the `changeSlug` blueprint option). The home and error pages can't be moved.

## Upgrading to Kirby 4

Delete the plugin folder from `site/plugins` (or run `composer remove owebstudio/kirby-move-pages`) before you update Kirby. If you keep it, it replaces the Move dialog of Kirby 4 and 5, and the page options show two Move entries.

Kirby 4 uses the `move` blueprint option as a permission (`true`, `false` or per role, like the other page options) and only allows parents with a pages section that accepts the page's template. Remove your lists of templates and queries from the `move` option. The role permission is `pages.move` instead of `owebstudio.move-pages.move`.
