<?php

namespace App\Enums;

enum Transport: string
{
    case Car = 'car';
    case Plane = 'plane';
    case Train = 'train';
    case Other = 'other';
}
