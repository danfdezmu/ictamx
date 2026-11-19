<?php

namespace App;

enum UserRole: string
{
    case Admin = 'admin';
    case Multimedia = 'multimedia';
    case Musico = 'musico';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Multimedia => 'Multimedia',
            self::Musico => 'Músico',
        };
    }

    public function managesGallery(): bool
    {
        return match ($this) {
            self::Admin, self::Multimedia => true,
            self::Musico => false,
        };
    }
}
