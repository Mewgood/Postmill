<?php

namespace App\Utils;

use SebastianBergmann\Diff\Differ as BaseDiffer;

final class Differ {
    private function __construct() {
    }

    /**
     * Diff in a format that's easy to work with in templates, and contains only
     * what we want (changed lines).
     *
     * @return array[]
     */
    public static function diff(string $from, string $to): array {
        $from = preg_split('/\R/', $from);
        $to = preg_split('/\R/', $to);

        $output = [];
        $oldLineNo = 0;
        $newLineNo = 0;

        $diff = (new BaseDiffer())->diffToArray($from, $to);

        for ($i = 0, $len = \count($diff); $i < $len; $i++) {
            switch ($diff[$i][1]) {
            case BaseDiffer::OLD:
                $oldLineNo++;
                $newLineNo++;
                break;

            case BaseDiffer::ADDED:
                if ($i > 0 && $diff[$i - 1][1] === BaseDiffer::REMOVED) {
                    $newLineNo++;
                    $oldLineNo++;

                    $output[] = [
                        'type' => 'changed',
                        'oldLineNo' => $oldLineNo,
                        'newLineNo' => $newLineNo,
                        'old' => $diff[$i - 1][0],
                        'new' => $diff[$i][0],
                    ];
                } else {
                    $newLineNo++;

                    $output[] = [
                        'type' => 'added',
                        'newLineNo' => $newLineNo,
                        'new' => $diff[$i][0],
                    ];
                }

                break;

            case BaseDiffer::REMOVED:
                if ($i === $len - 1 || $diff[$i + 1][1] !== BaseDiffer::ADDED) {
                    $oldLineNo++;

                    $output[] = [
                        'type' => 'removed',
                        'oldLineNo' => $oldLineNo,
                        'old' => $diff[$i][0],
                    ];
                }

                break;

            default:
                throw new \UnexpectedValueException(sprintf(
                    'Differ: Unknown operator (%s)',
                    var_export($diff[$i][1], true)
                ));
            }
        }

        return $output;
    }
}
