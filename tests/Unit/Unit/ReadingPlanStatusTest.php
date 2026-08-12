<?php

namespace Tests\Unit\Unit;

use App\Enums\ReadingPlanStatus;
use PHPUnit\Framework\TestCase;

class ReadingPlanStatusTest extends TestCase
{
    /**
     * 各ステータスの値が正しい。
     */
    public function test_reading_plan_status_values_are_correct(): void
    {
        $this->assertSame(1, ReadingPlanStatus::NotStarted->value);
        $this->assertSame(2, ReadingPlanStatus::Reading->value);
        $this->assertSame(3, ReadingPlanStatus::Completed->value);
    }

    /**
     * 各ステータスの表示名が正しい。
     */
    public function test_reading_plan_status_labels_are_correct(): void
    {
        $this->assertSame('開始前', ReadingPlanStatus::NotStarted->label());
        $this->assertSame('進行中', ReadingPlanStatus::Reading->label());
        $this->assertSame('読了', ReadingPlanStatus::Completed->label());
    }

    /**
     * 各ステータスに対応したバッジ用CSSクラスが返される。
     */
    public function test_reading_plan_status_badge_classes_are_correct(): void
    {
        $this->assertSame(
            'bg-gray-100 text-gray-800',
            ReadingPlanStatus::NotStarted->badgeClass()
        );

        $this->assertSame(
            'bg-blue-100 text-blue-800',
            ReadingPlanStatus::Reading->badgeClass()
        );

        $this->assertSame(
            'bg-green-100 text-green-800',
            ReadingPlanStatus::Completed->badgeClass()
        );
    }
}
