<?php
declare(strict_types=1);

namespace App\Client;

use App\Http\ClientFactory;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Utils;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class HttpClientFactory implements ClientFactory
{
    private string $endpoint;
    private string $username;
    private string $apiKey;

    public function __construct(string $endpoint, string $username, string $apiKey)
    {
        $this->endpoint = $endpoint;
        $this->username = $username;
        $this->apiKey = $apiKey;
    }

    public function create(): ClientInterface
    {

        $stack = new HandlerStack();
        $stack->setHandler(Utils::chooseHandler());

        // Add authentication info
        $stack->push(Middleware::mapRequest(
            function (RequestInterface $r) {
                $body = $r->getBody();
                $data = json_decode((string) $body, true);
                if($data === null) {
                    return $r;
                }
                $data['username'] = $this->username;
                $data['api_key'] = $this->apiKey;
                return $r->withBody(
                    \GuzzleHttp\Psr7\Utils::streamFor(
                        json_encode($data)
                    )
                );
            }
        ));

        return new Client([
            'base_uri' => $this->endpoint,
            'handler' => $stack
        ]);
    }
}
