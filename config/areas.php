<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Find;
use Kirby\Filesystem\Dir;
use Kirby\Toolkit\Str;

return [
    'site' => function ($kirby) {
        return [
            'dropdowns' => [
                'page' => [
                    'pattern' => 'pages/(:any)',
                    'options' => function (string $path) use ($kirby) {
                        // find the right page for the dropdown
                        $page = Find::page($path);

                        // load the core dropdown definition
                        $dropdown = $page->panel()->dropdown();

                        // get page options
                        $move = $page->blueprint()->options()['move'] ?? null;

                        if (empty($move) === true) {
                            return $dropdown;
                        }

                        // append a separator
                        $dropdown[] = '-';

                        // append a new option
                        $dropdown[] = [
                            'icon' => 'share',
                            'text' => t('move'),
                            'dialog' => $page->panel()->url(true) . '/move'
                        ];

                        return $dropdown;
                    }
                ]
            ],
            'dialogs' => [
                'page.move' => [
                    'pattern' => 'pages/(:any)/move',
                    'load' => function (string $path) use ($kirby) {
                        $options = [];
                        $site = $kirby->site();
                        $page = Find::page($path);
                        $move = $page->blueprint()->options()['move'] ?? false;

                        if (is_string($move) === true) {
                            $parents = Str::query($move, [
                                'kirby' => $kirby,
                                'model' => $page,
                                'page' => $page,
                                'site' => $site
                            ]);
                        } elseif (is_array($move) === true) {
                            $parents = $site->index()->filterBy('intendedTemplate', 'in', $move);

                            if (in_array('site', $move) && $page->parent()) {
                                $options[] = [
                                    'text' => $site->title()->value() . ' (/)',
                                    'value' => '/'
                                ];
                            }
                        } else {
                            throw new InvalidArgumentException('Invalid move option');
                        }

                        if (is_a($parents, '\Kirby\Cms\Pages') === false) {
                            throw new InvalidArgumentException('Move option should be return `\Kirby\Cms\Pages` collection with using query or page templates');
                        }

                        foreach ($parents as $model) {
                            if ($page->parent() && $page->parent()->is($model) === true) {
                                continue;
                            }

                            if ($page->is($model) === true) {
                                continue;
                            }

                            $options[] = [
                                'text' => $model->title()->value() . ' (/' . $model->id() . ')',
                                'value' => $model->id()
                            ];
                        }

                        return [
                            'component' => 'k-form-dialog',
                            'props' => [
                                'fields' => [
                                    'parent' => [
                                        'label' => t('select.parent'),
                                        'type' => 'select',
                                        'icon' => 'page',
                                        'options' => $options,
                                        'empty' => true
                                    ]
                                ]
                            ]
                        ];
                    },
                    'submit' => function (string $path) {
                        $kirby = Kirby::instance();

                        // parent page
                        $parent = get('parent');

                        if (empty($parent) === true) {
                            throw new InvalidArgumentException('Please select parent page!');
                        }

                        // source page
                        $page = Find::page($path);
                        $parent = $parent === '/' ? $page->site() : Find::page($parent);

                        // new id after page will moved
                        $newId = $parent->is($page->site()) ? $page->uid() : ($parent->id() . '/' . $page->uid());

                        // trigger before hook
                        $kirby->trigger('page.move:before', [
                            'page' => $page,
                            'parent' => $parent->is($page->site()) ? null : $parent
                        ]);

                        if ($page->isDraft() === true) {
                            Dir::make($parent->root() . '/_drafts');
                            $root = $parent->root() . '/_drafts/' . $page->uid();
                        } else {
                            if ($page->isListed() === true) {
                                $root = $parent->root() . '/' . $page->num() . '_' . $page->uid();
                            } else {
                                $root = $parent->root() . '/' . $page->uid();
                            }
                        }

                        // clone the page
                        $newPage = $page->clone([
                            'content' => $page->content()->toArray(),
                            'parent' => $parent->is($page->site()) ? null : $parent,
                            'root' => $root
                        ]);

                        // move the page
                        Dir::move($page->root(), $newPage->root());

                        // update the collections
                        if ($page->isDraft() === true) {
                            $page->parentModel()->drafts()->remove($page);
                            $parent->drafts()->append($newId, $newPage);
                        } else {
                            $page->parentModel()->children()->remove($page);
                            $parent->children()->append($newId, $newPage);
                        }

                        // trigger after hook
                        $kirby->trigger('page.move:after', [
                            'newPage' => $newPage,
                            'oldPage' => $page,
                            'parent' => $parent->is($page->site()) ? null : $parent
                        ]);

                        // redirect after success
                        return [
                            'redirect' => $newPage->panel()->url()
                        ];
                    }
                ]
            ]
        ];
    },
];
