<?php

namespace App\Domain\IdentityAndAccess\Enums;

enum UserRole: string
{
    case Employee = 'employee';
    case Manager = 'manager';
    case Admin = 'admin';
}
