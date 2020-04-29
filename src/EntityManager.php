<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2020 Baptiste MEUNIER
 * @author    Baptiste MEUNIER <baptiste.meunier@vigicorp.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Package\Eloquent;

use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Package\Eloquent\Exception\RepositoryException;
use Berlioz\Package\Eloquent\Repository\RepositoryInterface;
use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Class EntityManager
 *
 * @package Berlioz\Package\Eloquent
 */
class EntityManager implements CoreAwareInterface
{
    use CoreAwareTrait;
    /** @var Capsule */
    private $capsule;
    /** @var \Berlioz\Core\Core */
    private $core;

    /**
     * EntityManager constructor.
     *
     * @param Capsule $capsule
     */
    public function __construct(Core $core, Capsule $capsule)
    {
        $this->setCore($core);
        $this->capsule = $capsule;
    }

    /**
     * Get query builder
     *
     * @param Capsule $capsule
     * @return \Illuminate\Database\DatabaseManager
     */
    public function getQueryBuilder()
    {
        return $this->capsule->getDatabaseManager();
    }

    /**
     * Get repository.
     *
     * @param string $class
     *
     * @return \Berlioz\Package\Eloquent\Repository\RepositoryInterface
     * @throws \Berlioz\Package\Eloquent\Exception\RepositoryException
     */
    public function getRepository(string $class): RepositoryInterface
    {
        try {
            $instantiator = $this->getCore()->getServiceContainer()->getInstantiator();
            $repository = $instantiator->newInstanceOf($class, ['entityManager' => $this, 'core' => $this->getCore()]);

            if (!$repository instanceof RepositoryInterface) {
                throw new RepositoryException('Not a valid repository');
            }

            return $repository;
        } catch (RepositoryException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new RepositoryException(sprintf('Unable to instance repository class "%s"', $class));
        }
    }
}