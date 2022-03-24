<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Find;
use Kirby\Panel\Field;
use Kirby\Panel\Panel;
use Kirby\Toolkit\Str;

Kirby::plugin('clicktonext/kirby-move-pages', [
    'translations' => [
        'en' => [
            'move' => 'Move',
            'select.parent' => 'Select parent',
        ],
        'de' => [
            'move' => 'Bewegung',
            'select.parent' => 'Eltern auswählen',
        ],
        'tr' => [
            'move' => 'Taşı',
            'select.parent' => 'Ebeveyn seçiniz',
        ]
    ],
    'areas' => [
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
                            try {
                                $page = Find::page($path);
                                $move = $page->blueprint()->options()['move'] ?? false;

                                if (is_string($move) === true) {
                                    $parents = Str::query($move, [
                                        'kirby' => $kirby,
                                        'site' => $kirby->site(),
                                        'model' => $page,
                                        'page' => $page
                                    ]);
                                } elseif (is_array($move) === true) {
                                    $parents = $kirby->site()->index()->filterBy('intendedTemplate', 'in', $move);
                                } else {
                                    throw new InvalidArgumentException('Invalid move option');
                                }

                                if (is_a($parents, '\Kirby\Cms\Pages') === false) {
                                    throw new InvalidArgumentException('Move option should be return `\Kirby\Cms\Pages` collection with using query or page templates');
                                }

                                $options = [];
                                foreach ($parents as $model) {
                                    if ($page->parent()->is($model) === true) {
                                        continue;
                                    }

                                    $options[] = [
                                        'text' => $model->title()->value() . ' (/' . $model->id() . ')',
                                        'value' => $model->id()
                                    ];
                                }
                            } catch (Exception $e) {
                                throw $e;
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
                            try {
                                // parent page
                                $parent = get('parent');

                                if (empty($parent) === true) {
                                    throw new InvalidArgumentException('Please select parent page!');
                                }

                                // source page
                                $page = Find::page($path);
                                $parent = Find::page($parent);
                                $source = $page->root();

                                // new id after page will moved
                                $newId = $parent->id() . '/' . $page->uid();

                                if ($page->isDraft() === true) {
                                    Dir::make($parent->root() . '/_drafts');
                                    $target = $parent->root() . '/_drafts/' . $page->uid();
                                } else {
                                    if ($page->isListed() === true) {
                                        $target = $parent->root() . '/' . $page->num() . '_' . $page->uid();
                                    } else {
                                        $target = $parent->root() . '/' . $page->uid();
                                    }
                                }

                                // move the page
                                Dir::move($source, $target);

                                $clone = $page->clone(['parent' => $parent]);

                                if ($page->isDraft() === true) {
                                    $page->parentModel()->drafts()->remove($page);
                                    $parent->drafts()->append($newId, $clone);
                                } else {
                                    $page->parentModel()->children()->remove($page);
                                    $parent->children()->append($newId, $clone);
                                }

                                // redirect after success
                                if ($newPage = $parent->childrenAndDrafts()->find($page->uid())) {
                                    return [
                                        'redirect' => $newPage->panel()->url()
                                    ];
                                }
                            } catch (Exception $e) {
                                throw $e;
                            }

                            return true;
                        }
                    ]
                ]
            ];
        },
    ]
]);
