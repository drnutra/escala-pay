<?php

namespace App\Http\Controllers;

use App\Events\DashboardLoading;
use App\Plugins\PluginExtensionRegistry;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\Product;
use App\Support\OrderCurrencyTotals;
use App\Support\ReportingPeriod;
use App\Services\TeamAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const PERIODS = ['hoje', 'ontem', '7dias', 'mes', 'ano', 'total'];

    private const CACHE_TTL_SECONDS = 300; // 5 minutes

    public function __invoke(Request $request): Response
    {
        $period = $request->query('period', 'hoje');
        if (! in_array($period, self::PERIODS, true)) {
            $period = 'hoje';
        }

        $tenantId = auth()->user()->tenant_id;
        $userId = auth()->id();
        $bust = ReportingPeriod::dashboardBustToken($tenantId);
        $dateSuffix = ReportingPeriod::dashboardCacheSuffix($period);
        $cacheKey = 'dashboard:'.($tenantId ?? 'global').':'.$period.':'.$dateSuffix.':b'.$bust.':u'.($userId ?? '0');
        $cacheTtl = in_array($period, ['hoje', 'ontem'], true) ? 60 : self::CACHE_TTL_SECONDS;

        $payload = Cache::remember($cacheKey, $cacheTtl, function () use ($tenantId, $period) {
            [$start, $end] = ReportingPeriod::boundsForDashboard($period);

            $ordersQuery = Order::forTenant($tenantId);
            if (auth()->user()?->isTeam()) {
                $allowed = app(TeamAccessService::class)->allowedProductIdsFor(auth()->user());
                $ordersQuery->whereIn('product_id', $allowed ?: ['__none__']);
            }
        ReportingPeriod::applyCreatedAtBounds($ordersQuery, $start, $end);

        $ordersCompleted = (clone $ordersQuery)->where('status', 'completed');
        $ordersPending = (clone $ordersQuery)->where('status', 'pending');
        $ordersRefunded = (clone $ordersQuery)->where('status', 'refunded');

        $vendasTotaisPorMoeda = [];
        try {
            $vendasTotaisPorMoeda = OrderCurrencyTotals::valorPorMoedaFromQuery($ordersQuery);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('DashboardController valorPorMoeda', [
                'message' => $e->getMessage(),
                'tenant_id' => $tenantId,
            ]);
            $fallbackTotal = (float) (clone $ordersQuery)->where('status', 'completed')->sum('amount');
            $vendasTotaisPorMoeda = $fallbackTotal > 0
                ? [['currency' => 'BRL', 'total' => round($fallbackTotal, 2)]]
                : [];
        }
        $brlRow = collect($vendasTotaisPorMoeda)->firstWhere('currency', 'BRL');
        $vendasTotais = $brlRow ? (float) $brlRow['total'] : 0.0;
        $quantidadeVendas = $ordersCompleted->count();
        $ticketMedio = $quantidadeVendas > 0 && $brlRow
            ? (float) $brlRow['total'] / $quantidadeVendas
            : 0.0;
        $vendasPendentes = (float) (clone $ordersQuery)->where('status', 'pending')->where('currency', 'BRL')->sum('amount');
        $reembolsosCount = $ordersRefunded->count();
        $reembolsosTotal = (float) (clone $ordersQuery)->where('status', 'refunded')->sum('amount');

        $formasPagamento = (clone $ordersQuery)
            ->where('status', 'completed')
            ->selectRaw('gateway, SUM(amount) as total, COUNT(*) as quantidade')
            ->groupBy('gateway')
            ->get()
            ->map(function ($row) {
                $label = $this->gatewayLabel($row->gateway);
                return [
                    'metodo' => $row->gateway ?? 'outro',
                    'label' => $label,
                    'total' => (float) $row->total,
                    'quantidade' => (int) $row->quantidade,
                ];
            })
            ->values()
            ->all();

        $graficoVendas = $this->buildGraficoVendas($tenantId, $period, $start, $end);

        $productsQuery = Product::forTenant($tenantId);
        if (auth()->user()?->isTeam()) {
            $allowed = app(TeamAccessService::class)->allowedProductIdsFor(auth()->user());
            $productsQuery->whereIn('id', $allowed ?: ['__none__']);
        }
        $quantidadeProdutos = $productsQuery->count();

            $sessionsQuery = CheckoutSession::forTenant($tenantId);
            if (auth()->user()?->isTeam()) {
                $allowed = app(TeamAccessService::class)->allowedProductIdsFor(auth()->user());
                $sessionsQuery->whereIn('product_id', $allowed ?: ['__none__']);
            }
            ReportingPeriod::applyCreatedAtBounds($sessionsQuery, $start, $end);

            $abandonadosVisit = (clone $sessionsQuery)
                ->whereAbandonmentVisitEligible()
                ->count();

            $abandonadosForm = (clone $sessionsQuery)
                ->whereAbandonmentFormEligible()
                ->count();

            $convertedSessions = (clone $sessionsQuery)
                ->where('step', CheckoutSession::STEP_CONVERTED)
                ->count();

            $totalSessoesPeriodo = (clone $sessionsQuery)->count();

            $abandonoCarrinho = $abandonadosVisit + $abandonadosForm;
            $comDados = (clone $sessionsQuery)
                ->whereIn('step', [CheckoutSession::STEP_FORM_STARTED, CheckoutSession::STEP_FORM_FILLED, CheckoutSession::STEP_CONVERTED])
                ->count();
            $funilCheckout = [
                'abertos' => $totalSessoesPeriodo,
                'dados' => $comDados,
                'pagos' => $convertedSessions,
            ];
            $taxaConversao = $totalSessoesPeriodo > 0
                ? round((float) $convertedSessions / $totalSessoesPeriodo * 100, 1)
                : 0.0;

            $comparacao = $this->buildComparacao($tenantId, $period);
            $pendentesCount = (clone $ordersQuery)->where('status', 'pending')->count();
            $topProdutos = $this->buildTopProdutos(clone $ordersQuery);
            [$serieAtual, $serieAnterior] = $this->buildSeries($tenantId, $period, $start, $end);

            return [
                'period' => $period,
                'comparacao' => $comparacao,
                'vendas_totais' => round($vendasTotais, 2),
                'vendas_totais_por_moeda' => $vendasTotaisPorMoeda,
                'vendas_pendentes' => round($vendasPendentes, 2),
                'pendentes_count' => $pendentesCount,
                'top_produtos' => $topProdutos,
                'grafico_vendas_anterior' => $serieAnterior,
                'quantidade_vendas' => $quantidadeVendas,
                'ticket_medio' => round($ticketMedio, 2),
                'formas_pagamento' => $formasPagamento,
                'taxa_conversao' => $taxaConversao,
                'abandono_carrinho' => $abandonoCarrinho,
                'funil_checkout' => $funilCheckout,
                'reembolsos_count' => $reembolsosCount,
                'reembolsos_total' => round($reembolsosTotal, 2),
                'quantidade_produtos' => $quantidadeProdutos,
                'grafico_vendas' => $serieAtual ?? $graficoVendas,
            ];
        });

        // Fora do cache: o feed de últimas vendas precisa estar sempre fresco.
        $payload['vendas_recentes'] = $this->buildVendasRecentes($tenantId);
        $hoje = ReportingPeriod::now();
        $payload['ultimos_7_dias'] = $this->sumByBucket($tenantId, $hoje->copy()->subDays(6)->startOfDay(), $hoje->copy()->endOfDay(), false);

        $data = new \ArrayObject($payload);
        event(new DashboardLoading($data));

        return Inertia::render('Dashboard/Index', array_merge($data->getArrayCopy(), [
            'layoutFullWidth' => true,
            'plugin_dashboard_widgets' => PluginExtensionRegistry::getDashboardWidgets(),
        ]));
    }

    /**
     * Últimos pedidos do tenant (qualquer status), para o feed do dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildVendasRecentes(?int $tenantId): array
    {
        $query = Order::forTenant($tenantId)->with(['user:id,name', 'product:id,name']);
        if (auth()->user()?->isTeam()) {
            $allowed = app(TeamAccessService::class)->allowedProductIdsFor(auth()->user());
            $query->whereIn('product_id', $allowed ?: ['__none__']);
        }

        return $query->latest('created_at')->latest('id')->limit(8)->get()->map(function (Order $o) {
            $meta = is_array($o->metadata) ? $o->metadata : (json_decode((string) $o->metadata, true) ?: []);

            return [
                'id' => $o->id,
                'cliente' => $o->user?->name ?: ($o->email ? strstr($o->email, '@', true) : 'Cliente'),
                'produto' => $o->product?->name ?? '—',
                'metodo' => $this->gatewayLabel($o->gateway),
                'parcelas' => isset($meta['installments']) ? (int) $meta['installments'] : null,
                'valor' => round((float) $o->amount, 2),
                'moeda' => $o->currency ?: 'BRL',
                'status' => $o->status,
                'criado_em' => $o->created_at?->toIso8601String(),
            ];
        })->values()->all();
    }

    /**
     * Produtos com maior faturamento aprovado no período.
     *
     * @return array<int, array{nome: string, total: float, quantidade: int}>
     */
    private function buildTopProdutos($ordersQuery): array
    {
        $rows = $ordersQuery->where('orders.status', 'completed')
            ->reorder()
            ->selectRaw('orders.product_id, SUM(orders.amount) as total, COUNT(*) as quantidade')
            ->groupBy('orders.product_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
        $names = Product::whereIn('id', $rows->pluck('product_id')->filter()->all())->pluck('name', 'id');

        return $rows->map(fn ($r) => [
            'nome' => (string) ($names[$r->product_id] ?? 'Produto removido'),
            'total' => round((float) $r->total, 2),
            'quantidade' => (int) $r->quantidade,
        ])->values()->all();
    }

    /**
     * Série contínua do período (zeros onde não houve venda) e a do período anterior,
     * alinhadas por posição. Hoje/ontem: por hora, com o dia anterior inteiro como referência.
     *
     * @return array{0: ?array, 1: ?array}
     */
    private function buildSeries(?int $tenantId, string $period, ?Carbon $start, ?Carbon $end): array
    {
        if ($start === null || $end === null) {
            return [null, null];
        }
        $now = ReportingPeriod::now();
        $hourly = in_array($period, ['hoje', 'ontem'], true);
        $lastDay = $end->copy()->min($now->copy()->endOfDay());

        if ($hourly) {
            $prevStart = $start->copy()->subDay();
            $prevEnd = $end->copy()->subDay();
        } else {
            $days = (int) $start->copy()->startOfDay()->diffInDays($lastDay->copy()->startOfDay()) + 1;
            $prevEnd = $start->copy()->subDay()->endOfDay();
            $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();
        }

        $atual = $this->sumByBucket($tenantId, $start, $hourly ? $end : $lastDay, $hourly);
        $anterior = $this->sumByBucket($tenantId, $prevStart, $prevEnd, $hourly);

        return [$atual, $anterior];
    }

    private function sumByBucket(?int $tenantId, Carbon $start, Carbon $end, bool $hourly): array
    {
        $query = Order::forTenant($tenantId)->where('status', 'completed');
        if (auth()->user()?->isTeam()) {
            $allowed = app(TeamAccessService::class)->allowedProductIdsFor(auth()->user());
            $query->whereIn('product_id', $allowed ?: ['__none__']);
        }
        ReportingPeriod::applyCreatedAtBounds($query, $start, $end);
        $tz = ReportingPeriod::timezone();
        $buckets = [];
        $query->select(['created_at', 'amount'])->orderBy('created_at')->chunk(1000, function ($orders) use (&$buckets, $tz, $hourly) {
            foreach ($orders as $order) {
                $k = $hourly ? (int) $order->created_at->timezone($tz)->format('G') : $order->created_at->timezone($tz)->format('Y-m-d');
                $buckets[$k] = ($buckets[$k] ?? 0.0) + (float) $order->amount;
            }
        });

        $out = [];
        if ($hourly) {
            for ($h = 0; $h <= 23; $h++) {
                $out[] = ['data' => (string) $h, 'total' => round((float) ($buckets[$h] ?? 0), 2)];
            }

            return $out;
        }
        for ($d = $start->copy()->startOfDay(); $d->lte($end); $d->addDay()) {
            $k = $d->format('Y-m-d');
            $out[] = ['data' => $k, 'total' => round((float) ($buckets[$k] ?? 0), 2)];
        }

        return $out;
    }

    /**
     * Mesmo recorte no período anterior, só até o mesmo ponto do período atual
     * (ex.: hoje até agora vs. ontem até a mesma hora). Null em "total".
     *
     * @return array{label: string, vendas_totais: float, quantidade_vendas: int}|null
     */
    private function buildComparacao(?int $tenantId, string $period): ?array
    {
        $now = ReportingPeriod::now();
        [$start, $end, $label] = match ($period) {
            'hoje' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay(), 'vs. ontem até agora'],
            'ontem' => [$now->copy()->subDays(2)->startOfDay(), $now->copy()->subDays(2)->endOfDay(), 'vs. anteontem'],
            '7dias' => [$now->copy()->subDays(13)->startOfDay(), $now->copy()->subDays(7)->endOfDay(), 'vs. 7 dias anteriores'],
            'mes' => [
                $now->copy()->startOfMonth()->subMonthNoOverflow(),
                $now->copy()->subMonthNoOverflow(),
                'vs. mês passado até hoje',
            ],
            'ano' => [$now->copy()->startOfYear()->subYear(), $now->copy()->subYear(), 'vs. ano passado até hoje'],
            default => [null, null, null],
        };
        if ($start === null) {
            return null;
        }

        try {
            $query = Order::forTenant($tenantId);
            if (auth()->user()?->isTeam()) {
                $allowed = app(TeamAccessService::class)->allowedProductIdsFor(auth()->user());
                $query->whereIn('product_id', $allowed ?: ['__none__']);
            }
            ReportingPeriod::applyCreatedAtBounds($query, $start, $end);

            $brl = collect(OrderCurrencyTotals::valorPorMoedaFromQuery($query))->firstWhere('currency', 'BRL');

            return [
                'label' => $label,
                'vendas_totais' => round($brl ? (float) $brl['total'] : 0.0, 2),
                'quantidade_vendas' => (clone $query)->where('status', 'completed')->count(),
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('DashboardController comparacao', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function gatewayLabel(?string $gateway): string
    {
        if ($gateway === null || $gateway === '') {
            return 'Outro';
        }
        $g = strtolower($gateway);
        if (str_contains($g, 'pix')) {
            return 'Pix';
        }
        if (str_contains($gateway, 'card') || str_contains($g, 'cartao') || str_contains($g, 'cartão') || str_contains($g, 'credito')) {
            return 'Cartão';
        }
        if (str_contains($g, 'boleto')) {
            return 'Boleto';
        }
        return ucfirst($gateway);
    }

    private function buildGraficoVendas(?int $tenantId, string $period, ?\Carbon\Carbon $start, ?\Carbon\Carbon $end): array
    {
        $query = Order::forTenant($tenantId)->where('status', 'completed');
        if (auth()->user()?->isTeam()) {
            $allowed = app(TeamAccessService::class)->allowedProductIdsFor(auth()->user());
            $query->whereIn('product_id', $allowed ?: ['__none__']);
        }

        ReportingPeriod::applyCreatedAtBounds($query, $start, $end);

        $isHourly = in_array($period, ['hoje', 'ontem'], true);
        $tz = ReportingPeriod::timezone();

        if ($isHourly) {
            $totalsByHour = [];
            $query->select(['created_at', 'amount'])->orderBy('created_at')->chunk(500, function ($orders) use (&$totalsByHour, $tz) {
                foreach ($orders as $order) {
                    $h = (int) $order->created_at->timezone($tz)->format('G');
                    $totalsByHour[$h] = ($totalsByHour[$h] ?? 0.0) + (float) $order->amount;
                }
            });

            $result = [];
            for ($h = 0; $h <= 23; $h++) {
                $result[] = [
                    'data' => (string) $h,
                    'total' => round((float) ($totalsByHour[$h] ?? 0), 2),
                ];
            }

            return $result;
        }

        $totalsByDate = [];
        $query->select(['created_at', 'amount'])->orderBy('created_at')->chunk(500, function ($orders) use (&$totalsByDate, $tz) {
            foreach ($orders as $order) {
                $d = $order->created_at->timezone($tz)->format('Y-m-d');
                $totalsByDate[$d] = ($totalsByDate[$d] ?? 0.0) + (float) $order->amount;
            }
        });
        ksort($totalsByDate);

        $out = [];
        foreach ($totalsByDate as $data => $total) {
            $out[] = ['data' => $data, 'total' => round($total, 2)];
        }

        return $out;
    }
}
