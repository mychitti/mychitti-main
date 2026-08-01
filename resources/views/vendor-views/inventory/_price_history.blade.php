{{--
    Purchase / sell price history for one inventory item, plus the average of each.

    Only transactions are averaged — goods bought at a price, and the item sold at a price.
    Prices that were merely set (item created, price edited, selling price set on a stock entry)
    are listed greyed out for context, because an item is priced before anything is bought and
    that opening figure has no stock behind it.

    Included by every item_detail view — the base one and the diverged module copies under
    app/Modules/*/views. Module view directories are only *prepended* to the finder
    (App\Http\Middleware\ResolveModuleViews), so a name they do not carry themselves falls
    through to this file. One copy, six hosts.

    Needs $item and $priceHistory (built by the InventoryPriceHistory trait). Renders nothing
    when $priceHistory is absent, so a controller that has not been wired up yet degrades to a
    missing section rather than a fatal error.
--}}
@php $priceHistory = $priceHistory ?? null; @endphp
@if ($priceHistory)
    @php
        $purchaseStat = $priceHistory['purchase'];
        $sellStat = $priceHistory['sell'];
        $phLimit = 50;
        $phTables = [
            [
                'title' => 'Purchase Price History',
                'variant' => 'purchase',
                'points' => $priceHistory['purchase_points'],
                'empty' => 'No purchase price recorded yet.',
            ],
            [
                'title' => 'Sell Price History',
                'variant' => 'sell',
                'points' => $priceHistory['sell_points'],
                'empty' => 'No selling price recorded yet.',
            ],
        ];
        $phHasContext = $priceHistory['purchase_points']->concat($priceHistory['sell_points'])
            ->contains(fn($p) => !$p['counted']);
    @endphp

    <style>
        .ph-section {
            background: #fff;
            border: 1px solid #e9edf3;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.03);
            margin: 0.5rem 0;
        }

        .ph-heading {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #667085;
            margin-bottom: 0;
        }

        .ph-note {
            font-size: 12px;
            color: #98a2b3;
        }

        .ph-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .ph-stat {
            border: 1px solid #e9edf3;
            border-radius: 8px;
            padding: 0.875rem 1rem;
            background: #fff;
        }

        .ph-stat-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 500;
            color: #667085;
        }

        .ph-stat-value {
            display: block;
            font-size: 1.25rem;
            font-weight: 600;
            color: #101828;
            margin: 0.25rem 0;
            font-variant-numeric: tabular-nums;
        }

        .ph-stat-value.is-up {
            color: #27ae60;
        }

        .ph-stat-value.is-down {
            color: #c82333;
        }

        .ph-stat-sub {
            display: block;
            font-size: 0.75rem;
            color: #98a2b3;
            line-height: 1.6;
        }

        .ph-panel {
            border: 1px solid #e9edf3;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
        }

        .ph-table-title {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #344054;
            margin-bottom: 0;
            padding: 0.65rem 0.9rem;
            background: #fafbfc;
            border-bottom: 1px solid #e9edf3;
        }

        .ph-table-title::before {
            content: '';
            display: inline-block;
            width: 3px;
            height: 13px;
            border-radius: 2px;
            margin-right: 8px;
            vertical-align: -1px;
            background: #b0b8c4;
        }

        .ph-panel-purchase .ph-table-title::before {
            background: #4a7dc9;
        }

        .ph-panel-sell .ph-table-title::before {
            background: #27ae60;
        }

        .ph-scroll {
            max-height: 320px;
            overflow-y: auto;
        }

        .ph-scroll table {
            margin-bottom: 0;
        }

        .ph-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #fafbfc;
            font-size: 0.75rem;
            font-weight: 600;
            color: #667085;
        }

        .ph-scroll tbody td {
            font-size: 0.8125rem;
            color: #101828;
            font-variant-numeric: tabular-nums;
        }

        .ph-scroll td:first-child,
        .ph-scroll th:first-child {
            padding-left: 0.9rem;
        }

        .ph-scroll td:last-child,
        .ph-scroll th:last-child {
            padding-right: 0.9rem;
        }

        .ph-old {
            color: #9aa3af;
            text-decoration: line-through;
        }

        /* A price that was set but never transacted — shown, but not part of the average. */
        .ph-context td {
            color: #a5adba;
            font-style: italic;
        }

        .ph-empty {
            padding: 2rem 1rem;
            text-align: center;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
    </style>

    <div class="ph-section">
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:8px;">
            <h3 class="ph-heading">Price History</h3>
            <span class="ph-note">Average of what the item was actually bought and sold at.</span>
        </div>

        <div class="ph-grid">
            <div class="ph-stat">
                <span class="ph-stat-label">Avg. Purchase Price</span>
                <span class="ph-stat-value">
                    {{ $purchaseStat['average'] !== null ? _price($purchaseStat['average']) : '—' }}
                </span>
                <span class="ph-stat-sub">
                    @if ($purchaseStat['count'])
                        {{ $purchaseStat['count'] }} {{ $purchaseStat['count'] == 1 ? 'purchase' : 'purchases' }}
                        @if ($purchaseStat['max'] > $purchaseStat['min'])
                            · {{ _price($purchaseStat['min']) }} – {{ _price($purchaseStat['max']) }}
                        @endif
                        <br>Current: {{ _price($item->landing_price ?? 0) }}
                    @else
                        Nothing purchased yet · Current: {{ _price($item->landing_price ?? 0) }}
                    @endif
                </span>
            </div>
            <div class="ph-stat">
                <span class="ph-stat-label">Avg. Sell Price</span>
                <span class="ph-stat-value">
                    {{ $sellStat['average'] !== null ? _price($sellStat['average']) : '—' }}
                </span>
                <span class="ph-stat-sub">
                    @if ($sellStat['count'])
                        {{ $sellStat['count'] }} {{ $sellStat['count'] == 1 ? 'sale' : 'sales' }}
                        @if ($sellStat['max'] > $sellStat['min'])
                            · {{ _price($sellStat['min']) }} – {{ _price($sellStat['max']) }}
                        @endif
                        <br>Current: {{ _price($item->selling_price ?? 0) }}
                    @else
                        Not sold yet · Current: {{ _price($item->selling_price ?? 0) }}
                    @endif
                </span>
            </div>
            <div class="ph-stat">
                <span class="ph-stat-label">Avg. Margin</span>
                <span
                    class="ph-stat-value @if ($priceHistory['margin'] !== null) {{ $priceHistory['margin'] >= 0 ? 'is-up' : 'is-down' }} @endif">
                    @if ($priceHistory['margin'] !== null)
                        {{ $priceHistory['margin'] < 0 ? '-' : '' }}{{ _price(abs($priceHistory['margin'])) }}
                    @else
                        —
                    @endif
                </span>
                <span class="ph-stat-sub">
                    @if ($priceHistory['margin_percent'] !== null)
                        {{ number_format($priceHistory['margin_percent'], 2) }}% on avg. purchase price
                    @else
                        Needs both a purchase and a sale
                    @endif
                </span>
            </div>
        </div>

        <div class="row mt-3">
            @foreach ($phTables as $phTable)
                <div class="col-lg-6 mb-3">
                    <div class="ph-panel ph-panel-{{ $phTable['variant'] }}">
                        <h5 class="ph-table-title">{{ $phTable['title'] }}</h5>
                        <div class="ph-scroll">
                            @if (count($phTable['points']))
                                <table class="table table-borderless table-thead-bordered table-align-middle card-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="border-0">Date</th>
                                            <th class="border-0">Source</th>
                                            <th class="border-0 text-right">Was</th>
                                            <th class="border-0 text-right">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($phTable['points']->take($phLimit) as $point)
                                            <tr @if (!$point['counted']) class="ph-context" title="Price was set, not transacted — not counted in the average" @endif>
                                                <td>{{ $point['at'] ? \Carbon\Carbon::parse($point['at'])->format('d M Y') : '—' }}
                                                </td>
                                                <td>
                                                    {{ $point['label'] }}
                                                    @if ($point['reference'])
                                                        <br><span class="text-muted">{{ $point['reference'] }}</span>
                                                    @endif
                                                    @if ($point['variation'])
                                                        <br><span
                                                            class="badge badge-soft-info">{{ ucwords($point['variation']) }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-right">
                                                    @if ($point['old_price'] !== null)
                                                        <span class="ph-old">{{ _price($point['old_price']) }}</span>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="text-right">{{ _price($point['price']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="ph-empty">{{ $phTable['empty'] }}</div>
                            @endif
                        </div>
                    </div>
                    @if (count($phTable['points']) > $phLimit)
                        <small class="text-muted">Showing the latest {{ $phLimit }} of
                            {{ count($phTable['points']) }} prices. The average above covers all of them.</small>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($phHasContext)
            <small class="text-muted"><em>Greyed rows are prices that were set rather than transacted — listed for
                    context, not counted in the average.</em></small>
        @endif
    </div>
@endif
