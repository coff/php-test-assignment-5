<?php

namespace Tests\unit;

use PHPUnit\Framework\TestCase;
use SocialPost\Dto\SocialPostTo;
use Statistics\Dto\ParamsTo;

class NoopCalculatorTest extends TestCase
{
    /** @var \Statistics\Calculator\NoopCalculator  */
    protected $object;

    /** @var ParamsTo */
    protected $params;

    public function buildSocialPostToStub($date, $authorId)
    {
        $postStub = $this->createStub(SocialPostTo::class);
        $postStub->method('getDate')->willReturn(new \DateTime($date));
        $postStub->method('getAuthorId')->willReturn($authorId);

        return $postStub;
    }


    public function setUp() : void {
        $this->object = new \Statistics\Calculator\NoopCalculator();
        $this->object->setParameters($this->params = new ParamsTo());
        $this->params->setStatName('test');
    }

    public function socialPostsProvider() {
        return [
            [[ // two posts per user per month
                ['2022-01-01','userX'],
                ['2022-01-02','userX'],
                ],
                '2022-01-01', '2022-01-31', [2]
            ],
            [[ // one post per user per month
                ['2022-01-01','userX'],
                ['2022-01-02','userY'],
                ],
                '2022-01-01', '2022-01-31', [1]
            ],
            [[// zero posts in date range
                ['2022-02-01','userX'],
                ['2022-02-01','userX'],
                ],
                '2022-01-01', '2022-01-31', []
            ],
            [[// zero posts overall
            ],
                '2022-01-01', '2022-01-31', []
            ],
            [[ // average of one post each month
                ['2022-01-01','userX'],
                ['2022-02-01','userY'],
            ],
                '2022-01-01', '2022-02-28', [1, 1]
            ],
            [[ // average of 1.5 posts post each month
                ['2022-01-01','userX'],
                ['2022-01-10','userX'],
                ['2022-01-20','userY'],
                ['2022-02-01','userY'],
                ['2022-02-10','userY'],
                ['2022-02-20','userX'],
            ],
                '2022-01-01', '2022-02-28', [1.5, 1.5]
            ],
        ];
    }

    /**
     * @dataProvider socialPostsProvider
     */
    public function testDoCalculate_willCalculateProperMonthlyAverages($posts, $startDate, $endDate, $expectedAverages)
    {
        $this->params->setStartDate(new \DateTime($startDate));
        $this->params->setEndDate(new \DateTime($endDate));

        foreach($posts as $post) {
            $this->object->accumulateData($this->buildSocialPostToStub($post[0], $post[1]));
        }

        $statisticsTo = $this->object->calculate();

        foreach ($statisticsTo->getChildren() as $key => $statsTo) {
            $this->assertEquals($expectedAverages[$key], $statsTo->getValue());
        }

        $this->assertEquals(count($expectedAverages), count($statisticsTo->getChildren()));
    }
}