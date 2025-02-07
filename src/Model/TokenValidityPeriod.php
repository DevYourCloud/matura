<?php

namespace App\Model;

enum TokenValidityPeriod: int
{
    case SEVEN_DAYS = 7;
    case THIRTY_DAYS = 30;
    case NINETY_DAYS = 90;
    case SIX_MONTHS = 180;
    case ONE_YEAR = 365;
}
