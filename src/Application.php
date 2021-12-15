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
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Application
{
    private EntityManagerInterface $em;
    private PackingSolver $solver;
    private LoggerInterface $logger;
    private ?ValidatorInterface $validator = null;

    public function __construct(
        PackingSolver $solver,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
        // ValidatorInterface $validator
    )
    {
        $this->em = $entityManager;
        $this->solver = $solver;
        $this->logger = $logger;
        // $this->validator = $validator;
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
            return new Response('400', ['content-type' => 'text/plain'], 'JSON expected');
        }

        // todo move outside?
        $constraint = new Collection([
            'products' => new Collection([
                'id' => new Type('string'),
                'width' => [
                    new Type('float'),
                    new Positive()
                ],
                'height' => [
                    new Type('float'),
                    new Positive()
                ],
                'length' => [
                    new Type('float'),
                    new Positive()
                ],
                'weight' => [
                    new Type('float'),
                    new Positive()
                ],
            ])
        ]);

//        $errors = $this->validator->validate($data, $constraint);
//        if(count($errors)) {
//            return new Response(400, ['content-type' => 'application/json'], json_encode($errors));
//        }

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

        return new Response(200, ['content-type' => 'application/json'], json_encode($outputData));
    }

}
