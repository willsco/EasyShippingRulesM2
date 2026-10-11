<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Test\Unit\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule\Collection;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule\CollectionFactory;
use WillSco\EasyShippingRulesM2\Model\Rule;
use WillSco\EasyShippingRulesM2\Model\RuleValidator;

class RuleValidatorTest extends TestCase
{
    /**
     * @var CollectionFactory&MockObject
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var RuleValidator
     */
    private RuleValidator $validator;

    protected function setUp(): void
    {
        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('scopeDate')->willReturn(new \DateTime('2026-10-11'));
        $this->validator = new RuleValidator($this->collectionFactory, $timezone);
    }

    public function testFiltersRulesByTodayOrTheSimulatedDay(): void
    {
        $today = $this->collection([1]);
        $today->method('addActiveFilter')->willReturnSelf();
        $today->expects($this->once())->method('addDateFilter')->with('2026-10-11');
        $simulated = $this->collection([1]);
        $simulated->method('addActiveFilter')->willReturnSelf();
        $simulated->expects($this->once())->method('addDateFilter')->with('2026-11-27');
        $this->collectionFactory->method('create')->willReturnOnConsecutiveCalls($today, $simulated);

        $this->assertSame('2026-10-11', $this->validator->getDate());
        $this->validator->getActiveRule(1);
        $this->assertSame(
            '2026-11-27',
            $this->validator->runAtDate('2026-11-27', function (): string {
                $this->validator->getActiveRule(1);
                return $this->validator->getDate();
            })
        );
        $this->assertSame('2026-10-11', $this->validator->getDate());
    }

    public function testLoadsOnlyActiveRules(): void
    {
        $collection = $this->collection([1]);
        $collection->expects($this->once())->method('addActiveFilter')->willReturnSelf();
        $collection->expects($this->never())->method('addFieldToFilter');
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($collection);

        $this->assertNotNull($this->validator->getActiveRule(1));
        $this->assertNull($this->validator->getActiveRule(2));
    }

    public function testChosenRulesReplaceTheEnabledOnesOnlyInsideTheCallback(): void
    {
        $simulated = $this->collection([2]);
        $simulated->expects($this->never())->method('addActiveFilter');
        $simulated->expects($this->once())->method('addFieldToFilter')
            ->with('main_table.rule_id', ['in' => [2]])
            ->willReturnSelf();
        $active = $this->collection([1]);
        $active->method('addActiveFilter')->willReturnSelf();
        $this->collectionFactory->expects($this->exactly(2))->method('create')
            ->willReturnOnConsecutiveCalls($simulated, $active);

        $inside = $this->validator->runWithActiveRules(
            ['2'],
            fn () => [$this->validator->getActiveRule(1), $this->validator->getActiveRule(2)]
        );

        $this->assertNull($inside[0]);
        $this->assertNotNull($inside[1]);
        $this->assertNotNull($this->validator->getActiveRule(1));
        $this->assertNull($this->validator->getActiveRule(2));
    }

    public function testNoChosenRulesMeansNoActiveRule(): void
    {
        $this->collectionFactory->expects($this->never())->method('create');

        $this->assertNull($this->validator->runWithActiveRules([], fn () => $this->validator->getActiveRule(1)));
    }

    public function testMemoizedRunEvaluatesEachRuleOncePerAddress(): void
    {
        $collection = $this->collection([1]);
        $collection->method('addActiveFilter')->willReturnSelf();
        $this->collectionFactory->method('create')->willReturn($collection);
        $rule = $this->validator->getActiveRule(1);
        $rule->expects($this->exactly(3))->method('validate')->willReturn(true);
        $address = $this->createMock(AbstractModel::class);
        $otherAddress = $this->createMock(AbstractModel::class);

        $this->validator->runMemoized(function () use ($address, $otherAddress): void {
            $this->assertTrue($this->validator->isValid(1, $address));
            $this->assertTrue($this->validator->isValid(1, $address));
            $this->assertTrue($this->validator->isValid(1, $otherAddress));
        });
        $this->validator->isValid(1, $address);
    }

    public function testResultsReachedThroughACycleAreNotMemoized(): void
    {
        $collection = $this->collection([1, 2]);
        $collection->method('addActiveFilter')->willReturnSelf();
        $this->collectionFactory->method('create')->willReturn($collection);
        $address = $this->createMock(AbstractModel::class);
        // Rule 1 is valid when rule 2 is not; rule 2 is valid when rule 1 is. Validating rule 1 first
        // sees rule 2 as not valid, while rule 2 on its own is valid.
        $this->validator->getActiveRule(1)->method('validate')
            ->willReturnCallback(fn ($model) => !$this->validator->isValid(2, $model));
        $this->validator->getActiveRule(2)->method('validate')
            ->willReturnCallback(fn ($model) => (bool)$this->validator->isValid(1, $model));

        $results = $this->validator->runMemoized(fn () => [
            $this->validator->isValid(1, $address),
            $this->validator->isValid(2, $address),
        ]);

        $this->assertSame(
            [$this->validator->isValid(1, $address), $this->validator->isValid(2, $address)],
            $results,
            'Same results as without memoization'
        );
    }

    public function testSortsByPriorityThenIdWithInactiveRulesLast(): void
    {
        $collection = $this->collection([1, 2, 3], [1 => 5, 2 => 0, 3 => 5]);
        $collection->method('addActiveFilter')->willReturnSelf();
        $this->collectionFactory->method('create')->willReturn($collection);

        $this->assertSame([2, 1, 3, 9], $this->validator->sortByPriority([9, 3, '1', 2]));
    }

    /**
     * Collection mock iterating over rules with the given IDs.
     *
     * @param int[] $ids
     * @param array<int, int> $sortOrders priority by rule ID (default 0)
     * @return Collection&MockObject
     */
    private function collection(array $ids, array $sortOrders = []): Collection
    {
        $rules = [];
        foreach ($ids as $id) {
            $rule = $this->createMock(Rule::class);
            $rule->method('getId')->willReturn($id);
            $rule->method('getSortOrder')->willReturn($sortOrders[$id] ?? 0);
            $rules[] = $rule;
        }
        $collection = $this->createMock(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($rules));

        return $collection;
    }
}
