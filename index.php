<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Find;
use Kirby\Panel\Field;
use Kirby\Panel\Panel;

Kirby::plugin('clicktonext/kirby-move-pages', [
    'translations' => [
        'en' => [
            'move' => 'Move',
            'select.parent' => 'Select Parent',
        ],
        'de' => [
            'move' => 'Bewegung',
            'select.parent' => 'Eltern auswählen',
        ],
        'tr' => [
            'move' => 'Taşı',
            'select.parent' => 'Ebeveyn Seçiniz',
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
                            $options = $page->blueprint()->options()['move'] ?? false;

                            if (is_array($options) === false) {
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
                        'load' => function (string $path) {
                            try {
                                $page = Find::page($path);
                                $move = $page->blueprint()->options()['move'] ?? false;

                                if (is_array($move) === false) {
                                    throw new InvalidArgumentException('Invalid move option');
                                }

                                $options = [];
                                foreach ($move as $id) {
                                    if ($item = Find::page($id)) {
                                        $options[] = [
                                            'text' => $item->title()->value(),
                                            'value' => $item->id()
                                        ];
                                    }
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
                                    $target = $parent->root() . '/' . $page->uid();
                                }

                                // move the page
                                Dir::move($source, $target);

                                if ($page->isDraft() === true) {
                                    $page->parentModel()->drafts()->remove($page);
                                    $parent->drafts()->append($newId, $page->clone(['parent' => $parent]));
                                } else {
                                    $page->parentModel()->children()->remove($page);
                                    $parent->children()->append($newId, $page->clone(['parent' => $parent]));
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
