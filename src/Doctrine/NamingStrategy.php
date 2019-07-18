<?php

namespace App\Doctrine;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Mapping\NamingStrategy as NamingStrategyInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

class NamingStrategy extends UnderscoreNamingStrategy {
    /**
     * Same as Doctrine's underscore naming strategy, except table names are
     * plural.
     */
    public function classToTableName($className) {
        return parent::classToTableName(Inflector::pluralize($className));
    }
}
