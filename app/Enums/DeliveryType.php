<?php

namespace App\Enums;

enum DeliveryType: string
{
    case PICKUP = 'pickup';
    case STANDARD = 'standard';
    case EXPRESS = 'express';
}
