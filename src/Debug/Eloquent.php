<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2020 Baptiste Meunier
 * @author    Baptiste Meunier <baptiste.meunier@vigicorp.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Package\Eloquent\Debug;

use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Core\Debug\AbstractSection;
use Berlioz\Core\Debug\Activity;
use Berlioz\Core\Debug\Section;
use Illuminate\Database\Connection;

/**
 * Class Eloquent (Debug section for Berlioz debugger)
 * 
 * @package Berlioz\Package\Eloquent\Debug
 */
class Eloquent extends AbstractSection implements Section, \Countable, CoreAwareInterface
{
    use CoreAwareTrait;

    /** @var array Queries */
    private $queries;
    /** @var Connection */
    private $connection;

    /**
     * Atlas constructor.
     *
     * @param \Berlioz\Core\Core $core
     */
    public function __construct(Core $core)
    {
        $this->setCore($core);
    }


    /////////////////////////
    /// SECTION INTERFACE ///
    /////////////////////////

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return var_export($this, true);
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function saveReport()
    {
        $debug = $this->getCore()->getDebug();

        if (!is_null($this->connection)) {
            $this->queries = $this->connection->getQueryLog();

            // Add queries to the timeline
            foreach ($this->queries as $query) {
                $activity =
                    (new Activity('Query', $this->getSectionName()))
                        ->start()
                        ->end()
                        ->setDetail($query['query']);
                $debug->getTimeLine()->addActivity($activity);
            }
        }
    }

    /**
     * Get section name.
     *
     * @return string
     */
    public function getSectionName(): string
    {
        return 'Eloquent ORM';
    }

    /**
     * @inheritdoc
     */
    public function getTemplateName(): string
    {
        return '@Berlioz-EloquentPackage/Twig/Debug/eloquent.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(['queries' => $this->queries]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->queries = $unserialized['queries'] ?? [];
    }

    ///////////////////////////
    /// COUNTABLE INTERFACE ///
    ///////////////////////////

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->getQueries());
    }

    ////////////////////
    /// USER DEFINED ///
    ////////////////////

    /**
     * Get queries.
     *
     * @return array
     */
    public function getQueries(): array
    {
        return $this->queries ?? [];
    }

    /**
     * Get total duration.
     *
     * @return float
     */
    public function getDuration(): float
    {
        if (empty($this->queries)) {
            return 0;
        }

        $duration =
            array_reduce(
                $this->queries,
                function ($time, $query) {
                    return $time + $query['time'];
                }
            );

        return floatval($duration  / 1000);
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
}