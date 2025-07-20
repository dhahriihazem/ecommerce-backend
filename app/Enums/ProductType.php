<?php

namespace App\Enums;

enum ProductType: string
{
    case FixedPrice = 'fixed_price';
    case Auction = 'auction';
}