<?php

namespace App\SubmissionFinder;

use App\Entity\Submission;
use Symfony\Component\HttpFoundation\Request;

class Pager implements \IteratorAggregate {
    /**
     * @var string[]
     */
    private $nextPageParams = [];

    /**
     * @var Submission[]
     */
    private $submissions = [];

    public static function getParamsFromRequest(string $sortBy, Request $request): array {
        if (!isset(SubmissionFinder::SORT_COLUMN_MAP[$sortBy])) {
            throw new \InvalidArgumentException("Invalid sort mode '$sortBy'");
        }

        $params = [];

        foreach (SubmissionFinder::SORT_COLUMN_MAP[$sortBy] as $column => $order) {
            $value = $request->query->get('next_'.$column);
            $type = SubmissionFinder::SORT_COLUMN_TYPES[$column];

            if (!\is_string($value) || !self::valueIsOfType($type, $value)) {
                // missing columns - no pagination
                return [];
            }

            $params[$column] = self::transformValue($type, $value);
        }

        // complete pager params
        return $params;
    }

    /**
     * @param Submission[]|iterable $submissions List of submissions, including
     *                                           one more than $maxPerPage to
     *                                           tell if there's a next page
     * @param int                   $maxPerPage
     * @param string                $sortBy      property to use for pagination
     */
    public function __construct(iterable $submissions, int $maxPerPage, string $sortBy) {
        if (!isset(SubmissionFinder::SORT_COLUMN_MAP[$sortBy])) {
            throw new \InvalidArgumentException("Invalid sort mode '$sortBy'");
        }

        $count = 0;

        foreach ($submissions as $submission) {
            if (++$count > $maxPerPage) {
                foreach (SubmissionFinder::SORT_COLUMN_MAP[$sortBy] as $column => $order) {
                    $accessor = $this->columnNameToAccessor($column);
                    $value = $submission->{$accessor}();

                    if ($value instanceof \DateTimeInterface) {
                        // ugly hack
                        $value = $value->format('c');
                    }

                    $this->nextPageParams['next_'.$column] = $value;
                }

                break;
            }

            $this->submissions[] = $submission;
        }
    }

    public function getIterator() {
        return new \ArrayIterator($this->submissions);
    }

    public function hasNextPage(): bool {
        return (bool) $this->nextPageParams;
    }

    /**
     * @throws \BadMethodCallException if there is no next page
     */
    public function getNextPageParams(): array {
        if (!$this->hasNextPage()) {
            throw new \BadMethodCallException('There is no next page');
        }

        return $this->nextPageParams;
    }

    public function isEmpty(): bool {
        return empty($this->submissions);
    }

    private function columnNameToAccessor(string $columnName): string {
        return 'get'.str_replace('_', '', ucwords($columnName, '_'));
    }

    private static function valueIsOfType(string $type, string $value): bool {
        switch ($type) {
        case 'datetimetz':
            try {
                return (bool) new \DateTime($value);
            } catch (\Exception $e) {
                return false;
            }
        case 'integer':
            return ctype_digit($value) && \is_int(+$value) &&
                $value >= -0x80000000 && $value <= 0x7fffffff;
        case 'bigint':
            // if this causes problems on 32-bit systems, the site operators
            // deserved it.
            return ctype_digit($value) && \is_int(+$value);
        default:
            throw new \InvalidArgumentException("Unexpected type '$type'");
        }
    }

    private static function transformValue(string $type, string $value) {
        switch ($type) {
        case 'datetimetz':
            return new \DateTime($value);
        case 'integer':
        case 'bigint':
            return +$value;
        default:
            throw new \InvalidArgumentException("Unexpected type '$type'");
        }
    }
}
