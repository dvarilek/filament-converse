<?php

namespace Dvarilek\FilamentConverse\Tests\Models;

use Dvarilek\FilamentConverse\Models\Concerns\Conversable;
use Dvarilek\FilamentConverse\Tests\database\factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends \Illuminate\Foundation\Auth\User
{
    use Conversable;
    use HasFactory;

    protected static string $factory = UserFactory::class;
}
