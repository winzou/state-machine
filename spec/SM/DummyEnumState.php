<?php

namespace spec\SM;

if (version_compare(PHP_VERSION, '8.1', '>=')) {
    enum DummyEnumState: string
    {
        case Checkout = 'checkout';
        case Pending = 'pending';
        case Confirmed = 'confirmed';
        case Cancelled = 'cancelled';
    }
} else {
    class DummyEnumState
    {
        const Checkout = 'checkout';
        const Pending = 'pending';
        const Confirmed = 'confirmed';
        const Cancelled = 'cancelled';
    }
}
