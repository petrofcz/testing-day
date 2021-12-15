<?php
declare(strict_types=1);

namespace App\Http;

use Psr\Http\Client\ClientInterface;

interface ClientFactory
{
    public function create(): ClientInterface;
}
