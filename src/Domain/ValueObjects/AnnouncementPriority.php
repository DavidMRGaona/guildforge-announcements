<?php

declare(strict_types=1);

namespace Modules\Announcements\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class AnnouncementPriority
{
    private const int MIN_PRIORITY = 1;

    private const int MAX_PRIORITY = 10;

    private const int DEFAULT_PRIORITY = 5;

    public function __construct(
        public int $value,
    ) {
        $this->validate($value);
    }

    public static function default(): self
    {
        return new self(self::DEFAULT_PRIORITY);
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isHigherThan(self $other): bool
    {
        return $this->value > $other->value;
    }

    public function isLowerThan(self $other): bool
    {
        return $this->value < $other->value;
    }

    private function validate(int $value): void
    {
        if ($value < self::MIN_PRIORITY || $value > self::MAX_PRIORITY) {
            throw new InvalidArgumentException(
                sprintf('Priority must be between %d and %d', self::MIN_PRIORITY, self::MAX_PRIORITY)
            );
        }
    }
}
