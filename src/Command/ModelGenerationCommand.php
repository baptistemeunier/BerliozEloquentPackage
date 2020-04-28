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

namespace Berlioz\Package\Eloquent\Command;

use Berlioz\CliCore\Command\AbstractCommand;
use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Package\Eloquent\EntityManagerAwareTrait;
use GetOpt\GetOpt;
use GetOpt\Option;
use Illuminate\Database\Capsule\Manager as Capsule;
use Krlove\EloquentModelGenerator\Config;
use Krlove\EloquentModelGenerator\EloquentModelBuilder;
use Krlove\EloquentModelGenerator\Generator;
use Krlove\EloquentModelGenerator\Helper\EmgHelper;
use Krlove\EloquentModelGenerator\Processor\CustomPrimaryKeyProcessor;
use Krlove\EloquentModelGenerator\Processor\CustomPropertyProcessor;
use Krlove\EloquentModelGenerator\Processor\ExistenceCheckerProcessor;
use Krlove\EloquentModelGenerator\Processor\FieldProcessor;
use Krlove\EloquentModelGenerator\Processor\NamespaceProcessor;
use Krlove\EloquentModelGenerator\Processor\RelationProcessor;
use Krlove\EloquentModelGenerator\Processor\TableNameProcessor;
use Krlove\EloquentModelGenerator\TypeRegistry;

/**
 * Command use to generate entity code from database schema
 *
 * Class ModelGenerationCommand
 * @package Berlioz\Package\Eloquent\Command
 */
class ModelGenerationCommand extends AbstractCommand
{
    use CoreAwareTrait;
    use EntityManagerAwareTrait;

    /**
     * @var Capsule
     */
    private $capsule;

    /**
     * ModelGenerationCommand constructor.
     * @param Core $core
     * @param Capsule $capsule
     */
    public function __construct(Core $core, Capsule $capsule)
    {
        $this->capsule = $capsule;
        $this->setCore($core);
    }

    /**
     * @return array
     */
    public static function getOptions(): array
    {
        return [
            (new Option('c', 'class', GetOpt::REQUIRED_ARGUMENT))
                ->setDescription('Target class Name'),
            (new Option('t', 'table', GetOpt::REQUIRED_ARGUMENT))
                ->setDescription('Table use for generation'),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getShortDescription(): ?string
    {
        return 'Generate entity code from database schema';
    }

    /**
     * @inheritDoc
     *
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Krlove\EloquentModelGenerator\Exception\GeneratorException
     */
    public function run(GetOpt $getOpt)
    {
        $config = $this->createConfig($getOpt);

        $generator = $this->buildGenerator();
        $model = $generator->generateModel($config);

        print sprintf("Model %s generated \n", $model->getName()->getName());
    }

    /**
     * Create config for code generation
     *
     * @return Config
     * @throws \Berlioz\Config\Exception\ConfigException
     */
    protected function createConfig(GetOpt $getOpt)
    {
        $config = [
            'class-name' => $getOpt->getOption('class'),
            'table-name' => $getOpt->getOption('table')
        ];

        if(empty($config['class-name'])) {
            print("Class name must be given \n");
            exit;
        }

        if(empty($config['table-name'])) {
            print("Table name must be given \n");
            exit;
        }

        return new Config($config, $this->getCore()->getConfig()->get('eloquent.generation'));
    }

    /**
     * Build Generator for code generation
     *
     * @return Generator
     */
    private function buildGenerator()
    {
        $databaseManager = $this->capsule->getDatabaseManager();
        $typeRegistry = new TypeRegistry($databaseManager);

        $databaseManager = $this->capsule->getDatabaseManager();
        $helper = new EmgHelper();
        $processorList = [
            new ExistenceCheckerProcessor($databaseManager),
            new ExistenceCheckerProcessor($databaseManager),
            new FieldProcessor($databaseManager, $typeRegistry),
            new NamespaceProcessor(),
            new RelationProcessor($databaseManager, $helper),
            new CustomPropertyProcessor(),
            new TableNameProcessor($helper),
            new CustomPrimaryKeyProcessor($databaseManager, $typeRegistry),
        ];

        $builder = new EloquentModelBuilder($processorList);

        return new Generator($builder, $typeRegistry);
    }
}