<?php

namespace spec\SM;

enum DummyEnumState:string
{
    case Checkout = 'checkout';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}