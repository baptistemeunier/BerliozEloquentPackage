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

namespace Berlioz\Package\Eloquent\Entity;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AbstractEntity
 *
 * @package Berlioz\Package\Eloquent\Entity
 */
abstract class AbstractEntity extends Model
{
    protected $connection = 'default';
}