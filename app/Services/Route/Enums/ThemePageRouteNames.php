<?php

namespace NovelCabinet\Services\Route\Enums {
    enum ThemePageRouteNames: string
    {
        case BookFinder = 'bookFinder';
        case UserDashboard = 'userDashboard';
        case Login = 'login';
        case ExternalRedirect = 'externalRedirect';
    }
}