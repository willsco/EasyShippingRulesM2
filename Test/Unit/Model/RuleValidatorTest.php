<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Test\Unit\Model;

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
        $this->validator = new RuleValidator($this->collectionFactory);
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

    /**
     * Collection mock iterating over rules with the given IDs.
     *
     * @param int[] $ids
     * @return Collection&MockObject
     */
    private function collection(array $ids): Collection
    {
        $rules = [];
        foreach ($ids as $id) {
            $rule = $this->createMock(Rule::class);
            $rule->method('getId')->willReturn($id);
            $rules[] = $rule;
        }
        $collection = $this->createMock(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($rules));

        return $collection;
    }
}
