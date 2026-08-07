<?php

namespace App\Enums;

enum ReadingPlanStatus: int
{
    case NotStarted = 1;
    case Reading = 2;
    case Completed = 3;

    /**
     * 読書状態の表示名を返す。
     */
    public function label(): string
    {
        return match ($this) {
            self::NotStarted => '開始前',
            self::Reading => '進行中',
            self::Completed => '読了',
        };
    }

    /**
     * 状態バッジのCSSクラスを返す。
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::NotStarted => 'bg-gray-100 text-gray-800',
            self::Reading => 'bg-blue-100 text-blue-800',
            self::Completed => 'bg-green-100 text-green-800',
        };
    }
}
