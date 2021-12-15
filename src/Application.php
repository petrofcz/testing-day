<?php

namespace App;

use App\Entity\Packaging;
use App\Model\Item;
use App\Model\PackingSolver;
use App\Model\SolutionNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class Application
{
    private EntityManagerInterface $em;
    private PackingSolver $solver;
    private LoggerInterface $logger;

    public function __construct(
        PackingSolver $solver,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    )
    {
        $this->em = $entityManager;
        $this->solver = $solver;
        $this->logger = $logger;
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        if($request->getMethod() !== 'POST') {
            return new Response('405');
        }

        $data = json_decode($request->getBody(), true);
        // todo add check for content-type header?
        // todo add size / rate limits?
        if($data === null) {
            return new Response('400', [], 'JSON expected');
        }

        // todo add validation (use symfony validator)

        try {
            $result = $this->solver->pack(
                array_map(
                    static fn(array $product) => new Item(
                        $product['id'],
                        $product['width'],
                        $product['height'],
                        $product['length'],
                        $product['weight'],
                    ),
                    $data['products']
                ),
                $this->em->getRepository(Packaging::class)->findAll()
            );

            $outputData = [
                'width' => $result->getWidth(),
                'height' => $result->getHeight(),
                'length' => $result->getLength(),
            ];
        } catch(SolutionNotFoundException $e) {
            return new Response(404);
        } catch(RuntimeException $e) {
            // todo: add support for fallback solver?
            return new Response(500);
        }

        return new Response(200, [], json_encode($outputData));
    }

}
