<?php
declare(strict_types=1);

namespace App\ThreeDBP;

use App\Entity\Packaging;
use App\Http\LogHelper;
use App\Model\Box;
use App\Model\Item;
use App\Model\PackingSolver;
use App\Model\SolutionNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class RemotePackingSolver implements PackingSolver
{
    const RESPONSE_STATUS_SUCCESS = 1;
    // other response statuses missing, because the API documentation is inconsistent with reality

    private ClientInterface $httpClient;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(
        ClientInterface $httpClient,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->em = $em;
        $this->logger = $logger;
    }

    public function pack(iterable $items, iterable $packagings): Box
    {
        $request = new Request(
            'POST',     // todo use constant?
            '/packer/pack',
            [],
            json_encode(
                $this->createRequestData($items, $packagings)
            ),
        );

        // todo catch exception
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            $this->logger->error(
                'Requesting 3DBP service failed.',
                [
                    // todo: check request is immutable (leak auth info must be avoided)
                    LogHelper::CONTEXT_REQUEST => $request,
                    LogHelper::CONTEXT_RESPONSE => $response,
                ]
            );
            throw new RuntimeException('3DBP service call failed.');
        }

        $responseData = json_decode((string) $response->getBody(), true);
        $responseData = $responseData['response'];

        if (
            $responseData === null
            || !isset($responseData['status'])
        ) {
            $this->logger->error(
                'Response from 3DBP malformed.',
                [
                    // todo: check request is immutable (leak auth info must be avoided)
                    LogHelper::CONTEXT_REQUEST => $request,
                    LogHelper::CONTEXT_RESPONSE => $response,
                ]
            );
            throw new RuntimeException('3DBP service call failed.');
        }

        if ($responseData['status'] != self::RESPONSE_STATUS_SUCCESS) {
            $this->logger->error(
                'Packing failed.',
                [
                    // todo: check request is immutable (leak auth info must be avoided)
                    LogHelper::CONTEXT_REQUEST => $request,
                    LogHelper::CONTEXT_RESPONSE => $response,
                    'errors' => $responseData['errors']
                ]
            );
            throw new RuntimeException('Packing failed');
        }

        if(count($responseData['errors'])) {
            $this->logger->warning(
                'Packing finished with warnings.',
                [
                    LogHelper::CONTEXT_REQUEST => $request,
                    LogHelper::CONTEXT_RESPONSE => $response,
                    'errors' => $responseData['errors']
                ]
            );
        }

        if (!empty($responseData['not_packed_items'])) {
            throw new SolutionNotFoundException();
        }

        // Filter configurations that didn't skip any items
        $binsPackedData = array_filter(
        // todo check for key presence
            $responseData['bins_packed'],
            static fn(array $binPackedData) => count($binPackedData['not_packed_items']) === 0
        );

        // Sort by relative space used desc
        usort(
            $binsPackedData,
            static fn($a, $b) => $b['bin_data']['used_space'] - $a['bin_data']['used_space']
        );

        if (!count($binsPackedData)) {
            throw new SolutionNotFoundException();
        }

        // Winning bin
        $binData = reset($binsPackedData);

        return new Box(
            $binData['bin_data']['w'],
            $binData['bin_data']['h'],
            $binData['bin_data']['d'],
        );
    }

    /**
     * @param Item[] $items
     * @param Packaging[] $packagings
     */
    public function createRequestData(iterable $items, iterable $packagings): array
    {
        // todo: use serializer?

        if (empty($packagings)) {
            throw new RuntimeException('No packagings found.');
        }

        $binsData = array_map(
            static fn(Packaging $packaging) => [
                'id' => $packaging->getId(),
                'w' => $packaging->getWidth(),
                'h' => $packaging->getHeight(),
                'd' => $packaging->getLength(),
                'max_wg' => $packaging->getMaxWeight(),
            ],
            $packagings
        );

        $itemsData = array_map(
            fn(Item $item) => [
                'id' => $item->getId(),
                'w' => $item->getWidth(),
                'h' => $item->getHeight(),
                'd' => $item->getLength(),
                'wg' => $item->getWeight(),
                'q' => 1,   // quantity
                // todo: add vertical rotation option?
            ],
            $items
        );

        return [
            'items' => $itemsData,
            'bins' => $binsData
        ];
    }
}
