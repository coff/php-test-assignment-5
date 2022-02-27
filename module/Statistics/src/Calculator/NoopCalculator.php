<?php

declare(strict_types = 1);

namespace Statistics\Calculator;

use SocialPost\Dto\SocialPostTo;
use Statistics\Dto\StatisticsTo;

class NoopCalculator extends AbstractCalculator
{
    protected const UNITS = 'posts';

    /**
     * @var array
     */
    private $totals = [];

    protected function doAccumulate(SocialPostTo $postTo): void
    {
        $monthKey = $postTo->getDate()->format('\M\o\n\t\h m, Y');
        $userKey = $postTo->getAuthorId();

        $this->totals[$monthKey][$userKey] = ($this->totals[$monthKey][$userKey] ?? 0) + 1;
    }

    protected function doCalculate(): StatisticsTo
    {
        $stats = new StatisticsTo();

        foreach ($this->totals as $splitPeriod => $userTotals) {
            $child = (new StatisticsTo())
                ->setName($this->parameters->getStatName())
                ->setSplitPeriod($splitPeriod)
                ->setValue(array_sum($userTotals) / count($userTotals))
                ->setUnits(self::UNITS);

            $stats->addChild($child);
        }

        // Remark: note there will be no "0" stats when number of posts is zero for given time split

        return $stats;
    }
}
