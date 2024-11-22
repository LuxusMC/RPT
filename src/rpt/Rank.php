<?php

declare(strict_types=1);

namespace rpt;

final readonly class Rank {

	public function __construct(
		public string $name,
		public string $format,
		public int $minRP
	) {}
}
