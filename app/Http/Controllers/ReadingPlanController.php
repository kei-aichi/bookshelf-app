<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\ReadingPlanIndexRequest;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧画面を表示する。
     */
    public function index(ReadingPlanIndexRequest $request): View
    {
        $validated = $request->validated();

        $currentStatus = isset($validated['status'])
            ? (int) $validated['status']
            : null;

        $readingPlans = ReadingPlan::query()
            ->where('user_id', Auth::id())
            ->with('book')
            ->when(
                $currentStatus !== null,
                fn ($query) => $query->where('status', $currentStatus)
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
        $this->authorize('update', $readingPlan);

        if ($readingPlan->status !== ReadingPlanStatus::NotStarted) {
            abort(403);
        }

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
        $this->authorize('update', $readingPlan);

        if ($readingPlan->status !== ReadingPlanStatus::Reading) {
            abort(403);
        }

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
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画を登録する。
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        ReadingPlan::create([
            'user_id' => $request->user()->id,
            'book_id' => $validated['book_id'],
            'status' => ReadingPlanStatus::NotStarted,
            'target_date' => $validated['target_date'],
            'completed_at' => null,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '新しい読書計画が作成されました。');
    }

    /**
     * 読書計画編集画面を表示する。
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画を更新する。
     */
    public function update(
        UpdateReadingPlanRequest $request,
        ReadingPlan $readingPlan
    ): RedirectResponse {
        $this->authorize('update', $readingPlan);

        $validated = $request->validated();

        $readingPlan->update([
            'target_date' => $validated['target_date'],
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画が更新されました。');
    }

    /**
     * 読書計画を削除する。
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画が削除されました。');
    }
}
