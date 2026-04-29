<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Alonti Catering – Global context & Homepage content
    |--------------------------------------------------------------------------
    */

    'help' => [
        'cafe' => [
            'name' => 'Alonti Catering Kitchen - Galleria',
            'address' => '5051 Westheimer Rd, Houston, TX 77056',
            'phone' => '(281) 888-2045',
            'hours' => 'Mon-Fri: 7:00 AM - 3:00 PM',
        ],
        'contacts' => [
            'generalManager' => [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@alonti.com',
                'phone' => '(281) 888-2046',
            ],
            'cateringSalesManager' => [
                'name' => 'Michael Chen',
                'email' => 'michael.chen@alonti.com',
                'phone' => '(281) 888-2047',
            ],
        ],
    ],

    'social_media' => [
        ['platform' => 'Facebook', 'url' => 'https://www.facebook.com/AlontiCatering'],
        ['platform' => 'Twitter', 'url' => 'https://twitter.com/AlontiCatering'],
        ['platform' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/alonti-catering'],
        ['platform' => 'Instagram', 'url' => 'https://www.instagram.com/AlontiCatering'],
    ],

    'hero' => [
        'headline' => 'Professional Catering. No Middlemen.',
        'headlineHighlight' => 'Since 1974.',
        'subhead' => 'Scratch-made meals, in-house drivers...',
        'cta' => [
            'primary' => ['label' => 'Browse Menu', 'action' => 'browse-menu'],
            'secondary' => ['label' => 'View All', 'action' => 'view-all'],
        ],
        'heroImageUrl' => 'https://images.unsplash.com/photo-1555244162-803834f70033?w=1200',
        'overlay' => [
            'title' => 'Your Dedicated Partner',
            'description' => 'We handle the logistics from preparation to clean-up...',
        ],
    ],

    'group_order_section' => [
        'badge' => 'Since 1974',
        'title' => 'Group Order Solutions',
        'description' => 'No middlemen—our own team handles every order...',
        'features' => [
            ['iconKey' => 'Users', 'title' => '100+ People', 'subtitle' => 'No minimums; last-minute welcome'],
            ['iconKey' => 'Clock', 'title' => 'White-Glove Delivery', 'subtitle' => 'Our own drivers and setup team'],
        ],
        'cards' => [
            ['iconKey' => 'Utensils', 'title' => 'Scratch-Made, Chef-Prepared', 'description' => 'Italian classics...'],
            ['iconKey' => 'Users', 'title' => 'Dedicated Partner', 'description' => 'One team from order to clean-up...'],
            ['iconKey' => 'Clock', 'title' => 'Reliability', 'description' => 'Built for high-stakes meetings...'],
        ],
        'cta' => [
            'primary' => ['label' => 'Start Group Order', 'action' => 'start-group-order'],
            'secondary' => ['label' => 'Learn More', 'action' => 'learn-more'],
        ],
    ],

    'testimonials' => [
        [
            'id' => 1,
            'name' => 'Sarah Johnson',
            'position' => 'Event Manager',
            'company' => 'Microsoft',
            'rating' => 4.9,
            'content' => 'Alonti has been our go-to catering partner...',
            'avatar' => 'SJ',
        ],
        [
            'id' => 2,
            'name' => 'David Martinez',
            'position' => 'Director of Operations',
            'company' => 'TechCorp',
            'rating' => 4.8,
            'content' => 'From board meetings to all-hands, Alonti delivers every time. The quality and professionalism are unmatched.',
            'avatar' => 'DM',
        ],
        [
            'id' => 3,
            'name' => 'Emily Chen',
            'position' => 'HR Manager',
            'company' => 'Global Finance Inc',
            'rating' => 5.0,
            'content' => 'We switched to Alonti two years ago and never looked back. Our teams actually look forward to catered days now.',
            'avatar' => 'EC',
        ],
        [
            'id' => 4,
            'name' => 'James Wilson',
            'position' => 'Conference Coordinator',
            'company' => 'Summit Events',
            'rating' => 4.7,
            'content' => 'Reliable, flexible, and the food is always fresh. They handle last-minute changes without a hitch.',
            'avatar' => 'JW',
        ],
        [
            'id' => 5,
            'name' => 'Lisa Park',
            'position' => 'Office Manager',
            'company' => 'Innovate Labs',
            'rating' => 4.9,
            'content' => 'Alonti makes corporate catering effortless. One point of contact, clear communication, and consistently great meals.',
            'avatar' => 'LP',
        ],
        [
            'id' => 6,
            'name' => 'Robert Taylor',
            'position' => 'VP of Administration',
            'company' => 'Pacific Holdings',
            'rating' => 4.8,
            'content' => 'We use Alonti for everything from small team lunches to 200-person events. They scale with us and never disappoint.',
            'avatar' => 'RT',
        ],
    ],

    'featured_products_limit' => 5,
    'testimonials_limit' => 6,
];
