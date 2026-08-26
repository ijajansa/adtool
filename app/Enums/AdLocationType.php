<?php

namespace App\Enums;

enum AdLocationType: string
{
    case Country = 'country';
    case State = 'state';
    case City = 'city';
    case Radius = 'radius';
}
