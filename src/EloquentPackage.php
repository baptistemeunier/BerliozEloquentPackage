<?php

namespace Berlioz\Package\Eloquent;

use Berlioz\Config\ExtendedJsonConfig;
use Berlioz\Core\Core;
use Berlioz\Core\Package\AbstractPackage;
use Berlioz\Package\Eloquent\Exception\EloquentException;
use Berlioz\ServiceContainer\Service;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;

class EloquentPackage extends AbstractPackage
{
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
        $entityManagerService = new Service(EntityManager::class, 'entityManager');
        self::addService($core, $entityManagerService);
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function init(): void
    {
        // TODO Add debut section in Berlioz Debug
        // if ($this->getCore()->getDebug()->isEnabled()) {
            //$this::$debugSection = new Debug\Eloquent($this->getCore());
            //$this->getCore()->getDebug()->addSection($this::$debugSection);
        // }
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

        $resolver = new ConnectionResolver();
        $resolver->addConnection('default', $capsule->getConnection('default'));
        $resolver->setDefaultConnection('default');

        Model::setConnectionResolver($resolver);

        $capsule->bootEloquent();

        return $capsule;
    }
}