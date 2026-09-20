<?php

namespace App\Enums;

/**
 * Mirrors User::Active / User::Deactive. Only Active may hold a token.
 */
enum UserStatus: int
{
    case Active = 1;
    case Deactive = 2;

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Deactive => 'Deactive',
        };
    }
}
