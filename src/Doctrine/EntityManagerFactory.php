<?php
declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManagerInterface;

class EntityManagerFactory
{
    private string $host;
    private string $user;
    private string $password;
    private string $dbname;

    public function __construct(string $host, string $user, string $password, string $dbname)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->dbname = $dbname;
    }

    public function create(): EntityManagerInterface
    {
        $config = Setup::createAnnotationMetadataConfiguration([__DIR__ . '/..'], true, null, null, false);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());

        return EntityManager::create([
            'driver' => 'pdo_mysql',
            'host' => $this->host,
            'user' => $this->user,
            'password' => $this->password,
            'dbname' => $this->dbname,
        ], $config);

    }
}
