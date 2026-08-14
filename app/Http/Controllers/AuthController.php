<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(private readonly JwtService $jwt) {}

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'senha' => ['required', 'string', 'min:6'],
        ], [
            'nome.required' => 'Informe o nome.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'senha.required' => 'Informe a senha.',
            'senha.min' => 'A senha deve ter pelo menos 6 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'mensagem' => $validator->errors()->first(),
                'erros' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $validator->validated()['nome'],
            'email' => strtolower($validator->validated()['email']),
            'password' => $validator->validated()['senha'],
        ]);

        return response()->json([
            'status' => true,
            'mensagem' => 'Cadastro realizado com sucesso!',
            'usuario' => $this->userData($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'senha' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'mensagem' => 'Informe e-mail e senha válidos.',
            ], 422);
        }

        $credentials = $validator->validated();
        $user = User::where('email', strtolower($credentials['email']))->first();

        if (! $user || ! Hash::check($credentials['senha'], $user->password)) {
            return response()->json([
                'status' => false,
                'mensagem' => 'E-mail ou senha incorretos.',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'mensagem' => 'Login realizado com sucesso!',
            'token' => $this->jwt->createFor($user),
            'tipo_token' => 'Bearer',
            'expira_em' => (int) config('jwt.ttl') * 60,
            'usuario' => $this->userData($user),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'usuario' => $this->userData($request->user()),
        ]);
    }

    public function users(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'usuarios' => User::query()
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => $this->userData($user)),
        ]);
    }

    /** @return array{id: int, nome: string, email: string} */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'nome' => $user->name,
            'email' => $user->email,
        ];
    }
}
