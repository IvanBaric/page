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

    'default_status' => 'draft',

    'statuses' => [
        'draft' => ['label' => 'Skica'],
        'published' => ['label' => 'Objavljeno'],
        'archived' => ['label' => 'Arhivirano'],
    ],

    'default_template' => 'classic',

    'templates' => [
        'classic' => ['label' => 'Klasični'],
        'magazine' => ['label' => 'Magazinski'],
        'product_first' => ['label' => 'Proizvodi u prvom planu'],
    ],

    'section_types' => [
        'hero' => ['label' => 'Uvodni blok'],
        'features' => ['label' => 'Prednosti'],
        'about' => ['label' => 'O nama'],
        'statistics' => ['label' => 'Statistika'],
        'cta' => ['label' => 'Poziv na akciju'],
        'contact_preview' => ['label' => 'Kontakt'],
        'faq' => ['label' => 'Česta pitanja'],
        'partners' => ['label' => 'Partners'],
        'custom' => ['label' => 'Prilagođeno'],
    ],

    'section_creator' => [
        'exclude_types' => ['hero'],
        'groups' => [],
    ],

    'admin_section_definitions' => [],

    'admin_sections' => [],

    'admin_pages' => ['home', 'about', 'products', 'posts', 'gallery', 'contact'],

    'public_slugs' => [],

    'section_editors' => [
        'default' => 'admin.pages.sections.configured-items-editor',
    ],

    'admin_index' => [
        'parts' => ['pages', 'header', 'footer'],
        'per_page' => 12,
        'legacy_slugs' => ['o-udruzi'],
        'system_slugs' => ['header', 'footer'],
        'singleton_model' => null,
        'singleton_active_scope' => 'active',
        'missing_singleton_text' => 'Javna organizacija još nije dostupna za trenutni tim.',
        'public_route' => [
            'name' => null,
            'subject_parameter' => 'organizationSlug',
            'page_parameter' => 'pageSlug',
        ],
        'template_parts' => [
            'header' => [
                'definition_key' => 'template_header',
                'template' => null,
                'unsupported_text' => 'Za ovaj template još nije definirana Livewire komponenta zaglavlja.',
            ],
            'footer' => [
                'definition_key' => 'template_footer',
                'template' => null,
                'unsupported_text' => 'Za ovaj template još nije definirana Livewire komponenta podnožja.',
            ],
        ],
    ],

    'configured_items_editor' => [
        'actions' => [
            'save_item' => null,
            'toggle_item' => null,
            'reorder_item' => null,
            'delete_item' => null,
            'save_section' => null,
        ],
    ],

    'defaults' => [
        'section_visible' => true,
        'item_visible' => true,
    ],

    'one_page_navigation' => [
        'home_section_defaults' => [],
    ],

    'routes' => [
        'middleware' => ['web', 'auth'],
    ],

    'public_site' => [
        'enabled' => false,
        'view' => 'pages::public-site.page',
        'view_subject_variable' => 'subject',
        'layout' => 'layouts.public',
        'subject_resolver' => null,
        'view_tracker' => null,
        'subject' => [
            'model' => null,
            'request_attribute' => null,
            'slug_column' => 'slug',
            'tenant_column' => 'team_id',
            'active_column' => 'is_active',
            'active_value' => true,
            'eager_load' => [],
        ],
        'route' => [
            'enabled' => false,
            'uri' => '/{subjectSlug}/{pageSlug?}',
            'name' => 'pages.public.show',
            'middleware' => ['web'],
            'subject_parameter' => 'subjectSlug',
            'page_parameter' => 'pageSlug',
            'where' => [],
        ],
        'content_route' => [
            'enabled' => false,
            'uri' => '/{subjectSlug}/{pageSlug}/{contentSlug}',
            'name' => null,
            'middleware' => ['web'],
            'content_parameter' => 'contentSlug',
            'where' => [],
        ],
        'content_providers' => [],
    ],

    'public_management' => [
        'enabled' => false,
        'event' => 'pages-open-public-management',
        'modal_name' => 'public-management',
        'modal_class' => 'w-full md:w-2xl xl:w-5xl',
        'panels' => [],
    ],

    'admin' => [
        'routes' => true,
        'name_prefix' => 'admin.pages.',
        'prefix' => 'admin/pages',
        'middleware' => ['web', 'auth'],
    ],

    'admin_routes' => [
        'page_index' => 'admin.pages.index',
        'page_archive' => 'admin.pages.archive',
        'page_show' => 'admin.pages.show',
        'section_show' => 'admin.sections.show',
    ],

    'translatable' => [
        'default_locale' => null,
    ],

    'admin_ui' => [
        'layout' => 'layouts.app',
    ],

    'permissions' => [
        [
            'name' => 'pages',
            'slug' => 'pages',
            'label' => 'pages::permissions.group',
            'description' => 'pages::permissions.description',
            'icon' => 'file-text',
            'sort_order' => 20,
            'items' => [
                ['name' => 'View', 'slug' => 'view', 'code' => 'pages.view', 'label' => 'pages::permissions.view', 'sort_order' => 10],
                ['name' => 'Create', 'slug' => 'create', 'code' => 'pages.create', 'label' => 'pages::permissions.create', 'sort_order' => 20],
                ['name' => 'Update', 'slug' => 'update', 'code' => 'pages.update', 'label' => 'pages::permissions.update', 'sort_order' => 30],
                ['name' => 'Delete', 'slug' => 'delete', 'code' => 'pages.delete', 'label' => 'pages::permissions.delete', 'sort_order' => 40],
                ['name' => 'Publish', 'slug' => 'publish', 'code' => 'pages.publish', 'label' => 'pages::permissions.publish', 'sort_order' => 50],
                ['name' => 'Manage sections', 'slug' => 'manage_sections', 'code' => 'pages.sections.manage', 'label' => 'pages::permissions.sections_manage', 'sort_order' => 60],
            ],
        ],
    ],

    'features' => [
        'admin_routes' => true,
    ],
];
