<?php

namespace App\Entity\Page;

use PagerWave\DefinitionGroupTrait;
use PagerWave\DefinitionInterface as Definition;
use PagerWave\Validator\ValidatingDefinitionInterface as ValidatingDefinition;

final class CommentPage implements Definition, ValidatingDefinition {
    use DefinitionGroupTrait;

    public function getFieldNames(): array {
        return ['timestamp', 'id'];
    }

    public function isFieldDescending(string $fieldName): bool {
        return true;
    }

    public function isFieldValid(string $fieldName, $value): bool {
        switch ($fieldName) {
        case 'timestamp':
            return strtotime($value) !== false;
        case 'id':
            return is_numeric($value) && \is_int(+$value);
        default:
            return false;
        }
    }
}
