<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AiRoadmap;
use App\Models\AiSummary;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_endpoints_return_database_data_for_authenticated_user_only(): void
    {
        $user = User::factory()->create(['name' => 'Caio Henrique']);
        $otherUser = User::factory()->create();
        $headers = $this->authHeaders($user);

        Carbon::setTestNow('2026-08-14 22:57:36');

        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'HoraDev',
            'description' => 'Plataforma pessoal',
            'progress' => 72,
            'icon' => 'folder',
            'tone' => 'orange',
            'color' => '#ff6b00',
            'status' => 'active',
        ]);
        Project::create([
            'user_id' => $user->id,
            'name' => 'Projeto pausado',
            'status' => 'paused',
        ]);
        Project::create([
            'user_id' => $otherUser->id,
            'name' => 'Projeto de outro usuário',
            'status' => 'active',
        ]);

        Task::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'Tarefa concluída',
            'completed_at' => now()->subDay(),
        ]);
        Task::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'Tarefa antiga',
            'completed_at' => now()->subMonth(),
        ]);
        Task::create([
            'user_id' => $otherUser->id,
            'title' => 'Tarefa de outro usuário',
            'completed_at' => now(),
        ]);

        Comment::create(['user_id' => $user->id, 'content' => 'Novo comentário']);
        Comment::create(['user_id' => $user->id, 'content' => 'Já lido', 'read_at' => now()]);
        Comment::create(['user_id' => $otherUser->id, 'content' => 'Comentário alheio']);

        TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'started_at' => '2026-08-14 20:12:36',
            'ended_at' => '2026-08-14 21:15:00',
        ]);
        TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'started_at' => '2026-08-13 19:00:00',
            'ended_at' => '2026-08-13 21:19:50',
        ]);
        TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'started_at' => '2026-08-14 21:15:00',
        ]);

        Activity::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'Você concluiu a tarefa',
            'description' => 'Implementar autenticação JWT',
            'icon' => 'check_circle_outline',
            'occurred_at' => now()->subHour(),
        ]);
        Activity::create([
            'user_id' => $otherUser->id,
            'title' => 'Atividade alheia',
            'occurred_at' => now(),
        ]);

        AiRoadmap::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'description' => 'Próximos passos sugeridos para HoraDev.',
            'suggestions' => ['Adicionar notificações em tempo real', 'Implementar sistema de metas'],
        ]);
        AiSummary::create([
            'user_id' => $user->id,
            'title' => 'Você está focado e produtivo!',
            'description' => 'Continue mantendo a consistência.',
        ]);

        $this->getJson('/api/dashboard/resumo.php', $headers)
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('usuario.nome', 'Caio Henrique')
            ->assertJsonPath('metricas.tempoHoje', '02:45')
            ->assertJsonPath('metricas.tempoHojeComparativo', '+18% vs ontem')
            ->assertJsonPath('metricas.projetosAtivos', 1)
            ->assertJsonPath('metricas.projetosPausados', 1)
            ->assertJsonPath('metricas.tarefasConcluidas', 2)
            ->assertJsonPath('metricas.tarefasSemana', 1)
            ->assertJsonPath('metricas.comentariosNaoLidos', 1)
            ->assertJsonPath('metricas.sequenciaDias', 2);

        $this->getJson('/api/dashboard/projetos-ativos.php', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'projetos')
            ->assertJsonPath('projetos.0.nome', 'HoraDev')
            ->assertJsonPath('projetos.0.progresso', 72);

        $this->getJson('/api/dashboard/tempo-por-projeto.php', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'projetos')
            ->assertJsonPath('projetos.0.nome', 'HoraDev')
            ->assertJsonPath('projetos.0.cor', '#ff6b00');

        $this->getJson('/api/dashboard/atividades.php', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'atividades')
            ->assertJsonPath('atividades.0.tempoRelativo', 'há 1h');

        $this->getJson('/api/dashboard/roadmap-ia.php', $headers)
            ->assertOk()
            ->assertJsonPath('descricao', 'Próximos passos sugeridos para HoraDev.')
            ->assertJsonCount(2, 'sugestoes');

        $this->getJson('/api/dashboard/resumo-ia.php', $headers)
            ->assertOk()
            ->assertJsonPath('resumo.title', 'Você está focado e produtivo!');

        $this->getJson('/api/dashboard/timer-atual.php', $headers)
            ->assertOk()
            ->assertJsonPath('timer.emAndamento', true)
            ->assertJsonPath('timer.projetoNome', 'HoraDev')
            ->assertJsonPath('timer.inicio', '2026-08-14 21:15:00')
            ->assertJsonPath('timer.tempoAtual', '01:42:36');
    }

    public function test_dashboard_returns_empty_contract_when_user_has_no_data(): void
    {
        $user = User::factory()->create();
        $headers = $this->authHeaders($user);

        $this->getJson('/api/dashboard/resumo.php', $headers)
            ->assertOk()
            ->assertJsonPath('metricas.tempoHoje', '00:00')
            ->assertJsonPath('metricas.tempoHojeComparativo', null)
            ->assertJsonPath('metricas.projetosAtivos', 0)
            ->assertJsonPath('metricas.sequenciaDias', 0);

        $this->getJson('/api/dashboard/projetos-ativos.php', $headers)
            ->assertJsonCount(0, 'projetos');
        $this->getJson('/api/dashboard/tempo-por-projeto.php', $headers)
            ->assertJsonPath('totalSemana', '0h 0m')
            ->assertJsonCount(0, 'projetos');
        $this->getJson('/api/dashboard/atividades.php', $headers)
            ->assertJsonCount(0, 'atividades');
        $this->getJson('/api/dashboard/roadmap-ia.php', $headers)
            ->assertJsonPath('descricao', null)
            ->assertJsonCount(0, 'sugestoes');
        $this->getJson('/api/dashboard/resumo-ia.php', $headers)
            ->assertJsonPath('resumo', null);
        $this->getJson('/api/dashboard/timer-atual.php', $headers)
            ->assertJsonPath('timer', null);
    }

    public function test_dashboard_requires_jwt_and_allows_cors_preflight(): void
    {
        $this->getJson('/api/dashboard/resumo.php')
            ->assertUnauthorized()
            ->assertJson([
                'status' => false,
                'mensagem' => 'Token não informado.',
            ]);

        $this->options('/api/dashboard/resumo.php', [], [
            'Origin' => 'http://localhost:9000',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Authorization, Content-Type',
        ])->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }

    private function authHeaders(User $user): array
    {
        $token = app(JwtService::class)->createFor($user);

        return ['Authorization' => "Bearer {$token}"];
    }
}
