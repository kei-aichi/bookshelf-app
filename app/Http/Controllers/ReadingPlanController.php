<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧画面を表示する。
     */
    public function index(Request $request): View
    {
        $currentStatus = $request->filled('status')
            ? $request->integer('status')
            : null;

        $readingPlans = ReadingPlan::query()
            ->where('user_id', Auth::id())
            ->with('book')
            ->when(
                $currentStatus !== null && $currentStatus !== '',
                fn ($query) => $query->where('status', (int) $currentStatus)
            )
            ->latest()
            ->get();

        return view('reading-plans.index', compact(
            'readingPlans',
            'currentStatus'
        ));
    }

    /**
     * 読書計画を進行中に更新する。
     */
    public function start(ReadingPlan $readingPlan): RedirectResponse
    {
        $readingPlan->update([
            'status' => ReadingPlanStatus::Reading,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書を開始しました。');
    }

    /**
     * 読書計画を読了に更新する。
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を読了にしました。');
    }

    /**
     * 読書計画作成画面を表示する。
     */
    public function create(): View
    {
        return view('reading-plans.create');
    }

    /**
     * 読書計画編集画面を表示する。
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画を削除する。
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画が削除されました。');
    }
}
