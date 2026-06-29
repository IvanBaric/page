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
        'singleton_team_scope' => 'forTeam',
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

    'admin_routes' => [
        'page_index' => 'admin.pages.index',
        'page_archive' => 'admin.pages.archive',
        'page_show' => 'admin.pages.show',
        'section_show' => 'admin.sections.show',
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
        'tenant_resolver' => 'IvanBaric\\Corexis\\Contracts\\TenantResolver',
        'tenant_column' => 'team_id',
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
