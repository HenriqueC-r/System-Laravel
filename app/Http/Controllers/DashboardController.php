<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AiRoadmap;
use App\Models\AiSummary;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $yesterdayStart = $todayStart->copy()->subDay();

        $todaySeconds = $this->secondsInPeriod(
            $user->id,
            $todayStart,
            $now,
        );
        $yesterdaySeconds = $this->secondsInPeriod(
            $user->id,
            $yesterdayStart,
            $todayStart,
        );

        $completedTasks = Task::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at');

        return response()->json([
            'status' => true,
            'usuario' => [
                'id' => $user->id,
                'nome' => $user->name,
            ],
            'metricas' => [
                'tempoHoje' => $this->formatClock($todaySeconds),
                'tempoHojeComparativo' => $this->comparison($todaySeconds, $yesterdaySeconds),
                'projetosAtivos' => Project::where('user_id', $user->id)->where('status', 'active')->count(),
                'projetosPausados' => Project::where('user_id', $user->id)->where('status', 'paused')->count(),
                'tarefasConcluidas' => (clone $completedTasks)->count(),
                'tarefasSemana' => (clone $completedTasks)
                    ->whereBetween('completed_at', [$now->copy()->startOfWeek(), $now])
                    ->count(),
                'comentariosNaoLidos' => Comment::where('user_id', $user->id)->whereNull('read_at')->count(),
                'sequenciaDias' => $this->streakDays($user->id, $now),
            ],
        ]);
    }

    public function activeProjects(Request $request): JsonResponse
    {
        $projects = Project::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'nome' => $project->name,
                'descricao' => $project->description,
                'progresso' => $project->progress,
                'icone' => $project->icon,
                'tom' => $project->tone,
            ]);

        return response()->json([
            'status' => true,
            'projetos' => $projects,
        ]);
    }

    public function timeByProject(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $now = now();
        $weekStart = $now->copy()->startOfWeek();

        $entries = $this->entriesOverlapping($userId, $weekStart, $now)
            ->whereHas('project', fn (Builder $query) => $query->where('user_id', $userId))
            ->with('project')
            ->get();

        $secondsByProject = $entries
            ->groupBy('project_id')
            ->map(fn (Collection $projectEntries): int => $projectEntries->sum(
                fn (TimeEntry $entry): int => $this->overlapSeconds($entry, $weekStart, $now),
            ));

        $projects = $secondsByProject
            ->map(function (int $seconds, int|string $projectId) use ($entries): array {
                $project = $entries->firstWhere('project_id', (int) $projectId)->project;

                return [
                    'id' => $project->id,
                    'nome' => $project->name,
                    'tempoMinutos' => intdiv($seconds, 60),
                    'tempoFormatado' => $this->formatDuration($seconds),
                    'cor' => $project->color,
                ];
            })
            ->sortByDesc('tempoMinutos')
            ->values();

        return response()->json([
            'status' => true,
            'totalSemana' => $this->formatDuration($secondsByProject->sum()),
            'projetos' => $projects,
        ]);
    }

    public function activities(Request $request): JsonResponse
    {
        $now = now();
        $activities = Activity::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('occurred_at')
            ->get()
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'titulo' => $activity->title,
                'descricao' => $activity->description,
                'tempoRelativo' => $this->relativeTime($activity->occurred_at, $now),
                'icone' => $activity->icon,
            ]);

        return response()->json([
            'status' => true,
            'atividades' => $activities,
        ]);
    }

    public function aiRoadmap(Request $request): JsonResponse
    {
        $roadmap = AiRoadmap::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->first();

        return response()->json([
            'status' => true,
            'descricao' => $roadmap?->description,
            'sugestoes' => $roadmap?->suggestions ?? [],
        ]);
    }

    public function aiSummary(Request $request): JsonResponse
    {
        $summary = AiSummary::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->first();

        return response()->json([
            'status' => true,
            'resumo' => $summary ? [
                'title' => $summary->title,
                'description' => $summary->description,
            ] : null,
        ]);
    }

    public function currentTimer(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $now = now();
        $entry = TimeEntry::query()
            ->where('user_id', $userId)
            ->whereNull('ended_at')
            ->where('started_at', '<=', $now)
            ->whereHas('project', fn (Builder $query) => $query->where('user_id', $userId))
            ->with('project')
            ->latest('started_at')
            ->first();

        return response()->json([
            'status' => true,
            'timer' => $entry ? [
                'emAndamento' => true,
                'projetoId' => $entry->project->id,
                'projetoNome' => $entry->project->name,
                'inicio' => $entry->started_at->format('Y-m-d H:i:s'),
                'tempoAtual' => $this->formatTimer(max(0, $entry->started_at->diffInSeconds($now, false))),
            ] : null,
        ]);
    }

    private function entriesOverlapping(int $userId, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return TimeEntry::query()
            ->where('user_id', $userId)
            ->where('started_at', '<', $end)
            ->where(function (Builder $query) use ($start): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>', $start);
            });
    }

    private function secondsInPeriod(int $userId, CarbonInterface $start, CarbonInterface $end): int
    {
        return $this->entriesOverlapping($userId, $start, $end)
            ->get()
            ->sum(fn (TimeEntry $entry): int => $this->overlapSeconds($entry, $start, $end));
    }

    private function overlapSeconds(TimeEntry $entry, CarbonInterface $periodStart, CarbonInterface $periodEnd): int
    {
        $start = $entry->started_at->greaterThan($periodStart) ? $entry->started_at : $periodStart;
        $entryEnd = $entry->ended_at ?? now();
        $end = $entryEnd->lessThan($periodEnd) ? $entryEnd : $periodEnd;

        return max(0, $start->diffInSeconds($end, false));
    }

    private function streakDays(int $userId, CarbonInterface $now): int
    {
        $trackedDates = [];

        TimeEntry::query()
            ->where('user_id', $userId)
            ->where('started_at', '<=', $now)
            ->get(['started_at', 'ended_at'])
            ->each(function (TimeEntry $entry) use (&$trackedDates, $now): void {
                $lastDay = ($entry->ended_at ?? $now)->copy()->min($now)->startOfDay();
                $day = $entry->started_at->copy()->startOfDay();

                while ($day->lessThanOrEqualTo($lastDay)) {
                    $dayEnd = $day->copy()->endOfDay();
                    if ($this->overlapSeconds($entry, $day, $dayEnd) > 0) {
                        $trackedDates[$day->toDateString()] = true;
                    }
                    $day->addDay();
                }
            });

        $cursor = $now->copy()->startOfDay();
        if (! isset($trackedDates[$cursor->toDateString()])) {
            $cursor->subDay();
        }

        $streak = 0;
        while (isset($trackedDates[$cursor->toDateString()])) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    private function comparison(int $todaySeconds, int $yesterdaySeconds): ?string
    {
        if ($yesterdaySeconds === 0) {
            return null;
        }

        $percentage = (int) round((($todaySeconds - $yesterdaySeconds) / $yesterdaySeconds) * 100);
        $prefix = $percentage > 0 ? '+' : '';

        return "{$prefix}{$percentage}% vs ontem";
    }

    private function formatClock(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function formatDuration(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);

        return sprintf('%dh %dm', intdiv($minutes, 60), $minutes % 60);
    }

    private function formatTimer(int $seconds): string
    {
        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60,
        );
    }

    private function relativeTime(CarbonInterface $date, CarbonInterface $now): string
    {
        $seconds = max(0, $date->diffInSeconds($now, false));

        return match (true) {
            $seconds < 60 => 'agora',
            $seconds < 3600 => 'há '.intdiv($seconds, 60).'m',
            $seconds < 86400 => 'há '.intdiv($seconds, 3600).'h',
            $seconds < 604800 => 'há '.intdiv($seconds, 86400).'d',
            default => 'há '.intdiv($seconds, 604800).' sem',
        };
    }
}
