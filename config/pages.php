<?php

use IvanBaric\Pages\Models\Page;
use IvanBaric\Pages\Models\Section;
use IvanBaric\Pages\Models\SectionItem;

return [
    'tables' => [
        'pages' => 'pages',
        'sections' => 'sections',
        'section_items' => 'section_items',
    ],

    'models' => [
        'page' => Page::class,
        'section' => Section::class,
        'section_item' => SectionItem::class,
    ],

    'team_resolver' => class_exists('App\\Resolvers\\TeamResolver') ? 'App\\Resolvers\\TeamResolver' : null,

    'default_status' => 'draft',

    'statuses' => [
        'draft' => ['label' => 'Draft'],
        'published' => ['label' => 'Published'],
        'archived' => ['label' => 'Archived'],
    ],

    'default_template' => 'classic',

    'templates' => [
        'classic' => ['label' => 'Classic'],
        'magazine' => ['label' => 'Magazine'],
        'product_first' => ['label' => 'Product first'],
    ],

    'section_types' => [
        'hero' => ['label' => 'Hero'],
        'features' => ['label' => 'Features'],
        'about' => ['label' => 'About'],
        'statistics' => ['label' => 'Statistics'],
        'cta' => ['label' => 'Call to action'],
        'contact_preview' => ['label' => 'Contact preview'],
        'faq' => ['label' => 'FAQ'],
        'partners' => ['label' => 'Partners'],
        'custom' => ['label' => 'Custom'],
    ],

    'defaults' => [
        'section_visible' => true,
        'item_visible' => true,
    ],

    'routes' => [
        'enabled' => true,
        'middleware' => ['web', 'auth'],
    ],

    'admin' => [
        'routes' => true,
        'name_prefix' => 'admin.pages.',
        'prefix' => 'admin/pages',
        'middleware' => ['web', 'auth'],
    ],

    'public' => [
        'routes' => false,
        'name_prefix' => 'pages.',
        'prefix' => '',
        'middleware' => ['web'],
    ],

    'pagination' => [
        'admin' => 15,
        'public' => 12,
    ],

    'translatable' => [
        'enabled' => true,
        'fields' => [
            'pages' => ['title', 'excerpt', 'content'],
            'sections' => ['title', 'subtitle', 'description', 'content', 'button_text'],
            'section_items' => ['title', 'subtitle', 'description', 'content', 'button_text'],
        ],
        'default_locale' => null,
    ],

    'slug' => [
        'source' => 'title',
        'column' => 'slug',
        'scoped_to_team' => true,
        'sanigen' => [
            'generator' => null,
            'method' => 'generate',
        ],
    ],

    'seo' => [
        'enabled' => true,
        'trait' => 'IvanBaric\\Seo\\Concerns\\HasSeo',
    ],

    'gallery' => [
        'enabled' => true,
        'trait' => 'IvanBaric\\Gallery\\Concerns\\HasGallery',
    ],

    'media' => [
        'enabled' => true,
        'image_columns' => true,
    ],

    'admin_ui' => [
        'enabled' => true,
        'layout' => 'layouts.app',
    ],

    'taxonomy' => [
        'enabled' => true,
        'trait' => 'IvanBaric\\Taxonomy\\Concerns\\HasTaxonomies',
    ],

    'status' => [
        'enabled' => true,
    ],

    'settings' => [
        'enabled' => true,
    ],

    'language' => [
        'enabled' => true,
    ],

    'audit' => [
        'enabled' => true,
    ],

    'corexis' => [
        'action_result' => 'IvanBaric\\Corexis\\Data\\ActionResult',
        'team_resolver' => 'IvanBaric\\Corexis\\Contracts\\TeamResolver',
    ],

    'features' => [
        'admin_routes' => true,
        'public_routes' => false,
        'soft_deletes' => true,
        'seo' => true,
        'gallery' => true,
        'media' => true,
        'taxonomy' => true,
        'settings' => true,
        'language' => true,
        'audit' => true,
    ],
];
