<?php

declare(strict_types=1);

namespace App\Enums;

enum AdminSettingsType: string
{
    case Site = 'site';
    case Seo = 'seo';
    case SocialLogin = 'social_login';
    case PaymentGateway = 'payment_gateway';
    case Notification = 'notification';
    case Storage = 'storage';
    case Redis = 'redis';
    case Search = 'search';
    case LegalPage = 'legal_page';
}
