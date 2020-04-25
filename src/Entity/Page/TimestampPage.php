<?php

namespace App\Entity\Page;

use PagerWave\DefinitionGroupTrait;
use PagerWave\DefinitionInterface as Definition;
use PagerWave\Validator\ValidatingDefinitionInterface as ValidatingDefinition;

final class TimestampPage implements Definition, ValidatingDefinition {
    use DefinitionGroupTrait;

    public function getFieldNames(): array {
        return ['timestamp'];
    }

    public function isFieldDescending(string $fieldName): bool {
        return true;
    }

    public function isFieldValid(string $fieldName, $value): bool {
        return strtotime($value) !== false;
    }
}
