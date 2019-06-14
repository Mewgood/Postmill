<?php

namespace App\Doctrine;

use Doctrine\ORM\Mapping\NamingStrategy as NamingStrategyInterface;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Symfony\Component\Inflector\Inflector;

class NamingStrategy extends UnderscoreNamingStrategy {
    /**
     * Same as Doctrine's underscore naming strategy, except table names are
     * plural.
     */
    public function classToTableName($className) {
        $className = \preg_replace('/^.*\\\\/', '', $className);
        $className = Inflector::pluralize($className);

        if (\is_array($className)) {
            $className = $className[0];
        }

        // change fora to forum
        $className = \preg_replace('/\b(for)a\b/i', '$1ums', $className);

        return parent::classToTableName($className);
    }
}
