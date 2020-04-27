<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2018 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Package\Eloquent\Repository;

use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Package\Eloquent\EntityManager;
use Berlioz\Package\Eloquent\EntityManagerAwareInterface;
use Berlioz\Package\Eloquent\EntityManagerAwareTrait;

/**
 * Class AbstractRepository.
 *
 * @package Berlioz\Package\Eloquent\Repository
 */
abstract class AbstractRepository implements EntityManagerAwareInterface, CoreAwareInterface, RepositoryInterface
{
    use CoreAwareTrait;
    use EntityManagerAwareTrait;

    /**
     * AbstractRepository constructor.
     *
     * @param \Berlioz\Core\Core $core
     * @param \Berlioz\Package\Eloquent\EntityManager $entityManager
     */
    public function __construct(Core $core, EntityManager $entityManager)
    {
        $this->setCore($core);
        $this->setEntityManager($entityManager);
    }
}