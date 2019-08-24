<?php

namespace App\Doctrine;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

class NamingStrategy extends UnderscoreNamingStrategy {
    /**
     * Same as Doctrine's underscore naming strategy, except table names are
     * plural.
     *
     * @param string $className
     */
    public function classToTableName($className): string {
        return parent::classToTableName(Inflector::pluralize($className));
    }
}
