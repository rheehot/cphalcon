<?php

namespace Phalcon\Test\Models\Some;

use Phalcon\Test\ModelRepositories\Some\RobottersRepository;

/**
 * Robotters
 *
 * "robotters" is robots in danish
 */
class Robotters extends \Phalcon\Mvc\Model
{
    public static function getRepositoryClass()
    {
        return RobottersRepository::class;
    }

    public function getRobottersDeles($arguments = null)
    {
        return $this->getRelated(RobottersDeles::class, $arguments);
    }
}
