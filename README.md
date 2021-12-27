# Kirby Move Pages

Missing move pages feature for Kirby 3. You can move the pages under the parent pages you specify with this plugin.

## Installation

### Installation with composer

### Manual

1. Download the latest release
2. Unzip downloaded file
3. Copy/paste unzipped folder in your `/site/plugins` folder

### With composer

```ssh
composer require clicktonext/kirby-move-pages
```

### Git submodule

```ssh
git submodule add https://github.com/clicktonext/kirby-move-pages.git site/plugins/kirby-move-pages
```

## Sample Use Case

### App Directory

- /home
- /about
- /projects
  - /ongoing
  - /completed
  - /archived
- /contact

### Blueprint

/site/blueprints/pages/project.yml
```yaml
title: Project

options:
  move:
    - projects/ongoing
    - projects/completed
    - projects/archived
```

A project page can now be moved to the ongoing, completed or archived pages.
