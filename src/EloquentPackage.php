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

namespace Berlioz\Package\Eloquent;

use Berlioz\Config\ExtendedJsonConfig;
use Berlioz\Core\Core;
use Berlioz\Core\Package\AbstractPackage;
use Berlioz\Package\Eloquent\Exception\EloquentException;
use Berlioz\ServiceContainer\Service;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EloquentPackage
 *
 * @package Berlioz\Package\Eloquent
 */
class EloquentPackage extends AbstractPackage
{
    /** @var \Berlioz\Package\Eloquent\Debug\Eloquent */
    private static $debugSection;

    /**
     * @inheritdoc
     * @throws \Berlioz\Config\Exception\ConfigException
     */
    public static function config()
    {
        return new ExtendedJsonConfig(
            implode(
                DIRECTORY_SEPARATOR,
                [
                    __DIR__,
                    '..',
                    'resources',
                    'config.default.json',
                ]
            ), true
        );
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Berlioz\ServiceContainer\Exception\ContainerException
     */
    public static function register(Core $core): void
    {
        // Create capsule service
        $atlasService = new Service(Capsule::class, 'capsule');
        $atlasService->setFactory(EloquentPackage::class . '::capsuleFactory');
        self::addService($core, $atlasService);

        // Create entity manager service
        $entityManagerService = new Service(EntityManager::class, 'entityManagerEloquent');
        self::addService($core, $entityManagerService);
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function init(): void
    {
        if ($this->getCore()->getConfig()->get('berlioz.debug', false)) {
            $this::$debugSection = new Debug\Eloquent($this->getCore());
            $this->getCore()->getDebug()->addSection($this::$debugSection);
        }
    }

    /////////////////
    /// FACTORIES ///
    /////////////////

    /**
     * Create Capsule instance
     *
     * @param Core $core
     * @return Capsule
     *
     * @throws EloquentException
     * @throws \Berlioz\Config\Exception\ConfigException
     */
    public static function capsuleFactory(Core $core): Capsule
    {
        $capsule = new Capsule();

        $connectionSettings = $core->getConfig()->get('eloquent.connection', []);
        if (empty($connectionSettings)) {
            throw new EloquentException(
                "You need to fill 'eloquent.connection' key in config. See documentation for more information."
            );
        }

        $capsule->addConnection($connectionSettings);
        $connection = $capsule->getConnection('default');

        $resolver = new ConnectionResolver();
        $resolver->addConnection('default', $connection);
        $resolver->setDefaultConnection('default');

        Model::setConnectionResolver($resolver);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Log queries?
        if((bool) $core->getConfig()->get('eloquent.log_queries', false) === true) {
            $connection->enableQueryLog();
        }
        // Debug activate?
        if ($core->getConfig()->get('berlioz.debug.enable', false)) {
            self::$debugSection->setConnection($connection);
        }

        return $capsule;
    }
}