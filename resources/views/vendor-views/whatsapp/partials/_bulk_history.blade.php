
        <div class="row" style="row-gap:12px;">
            <div class="col-sm-6 col-lg-3 wa-col">
                <div class="wa-stat">
                    <div>
                        <div class="wa-stat-val">{{ number_format($totals->runs ?? 0) }}</div>
                        <div class="wa-stat-lbl">Batches sent</div>
                    </div>
                    <div class="wa-stat-ico" style="background:#eef7ff;color:#2563eb;"><i class="tio-layers"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 wa-col">
                <div class="wa-stat">
                    <div>
                        <div class="wa-stat-val">{{ number_format($totals->recipients ?? 0) }}</div>
                        <div class="wa-stat-lbl">Numbers messaged</div>
                    </div>
                    <div class="wa-stat-ico" style="background:#eefbf3;color:#128c7e;"><i class="tio-user-big"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 wa-col">
                <div class="wa-stat">
                    <div>
                        <div class="wa-stat-val">{{ number_format($totals->last30 ?? 0) }}</div>
                        <div class="wa-stat-lbl">In the last 30 days</div>
                    </div>
                    <div class="wa-stat-ico" style="background:#fff7e8;color:#b45309;"><i class="tio-calendar-month"></i></div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 wa-col">
                <div class="wa-stat">
                    <div>
                        <div class="wa-stat-val {{ ($totals->failed ?? 0) ? 'text-danger' : '' }}">
                            {{ number_format($totals->failed ?? 0) }}
                        </div>
                        <div class="wa-stat-lbl">Failed to send</div>
                    </div>
                    <div class="wa-stat-ico" style="background:#fdecec;color:#dc2626;"><i class="tio-warning"></i></div>
                </div>
            </div>
        </div>

        <div class="wa-card">
            <div class="wa-card-h">
                Your bulk sends
                <span class="wa-sub">Newest first</span>
            </div>
            <div class="wa-card-b flush">
                @if ($runs->isEmpty())
                    <div class="wa-empty">
                        <i class="tio-send-outlined"></i>
                        <div class="wa-empty-t">No bulk messages sent yet</div>
                        <div class="wa-empty-s mb-3">
                            Once you send a batch from the composer, every number in it is listed here.
                        </div>
                        <a href="{{ route('vendor.whatsapp.bulk') }}" class="btn btn-sm btn--primary">
                            Send a bulk message
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table wa-table">
                            <thead>
                                <tr>
                                    <th>Sent</th>
                                    <th>Message</th>
                                    <th>Audience</th>
                                    <th class="text-center">Numbers</th>
                                    <th class="text-center">Sent</th>
                                    <th class="text-center">Failed</th>
                                    <th class="text-right">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($runs as $run)
                                    <tr>
                                        <td class="text-nowrap">
                                            <span class="font-weight-bold">
                                                {{ \Carbon\Carbon::parse($run->started_at)->format('d M Y') }}
                                            </span>
                                            <div class="wa-sub">
                                                {{ \Carbon\Carbon::parse($run->started_at)->format('h:i A') }}
                                                @if ($run->finished_at && $run->finished_at !== $run->started_at)
                                                    – {{ \Carbon\Carbon::parse($run->finished_at)->format('h:i A') }}
                                                @endif
                                            </div>
                                        </td>
                                        <td style="max-width:340px;">
                                            <span class="font-weight-bold">{{ $run->template ?: 'Template' }}</span>
                                            @if ($run->body)
                                                <div class="wa-sub" style="white-space:normal;">
                                                    {{ \Illuminate\Support\Str::limit($run->body, 110) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            {{ $run->audience === 'platform' ? 'MyChitti customers' : 'My customers' }}
                                        </td>
                                        <td class="text-center font-weight-bold">{{ number_format($run->recipients) }}</td>
                                        <td class="text-center text-success font-weight-bold">{{ number_format($run->sent) }}</td>
                                        <td class="text-center {{ $run->failed ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                            {{ number_format($run->failed) }}
                                        </td>
                                        <td class="text-right text-nowrap">
                                            <a href="{{ route('vendor.whatsapp.bulk.history.run', $run->run_id) }}"
                                               class="btn btn-sm btn-outline-secondary">View numbers</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2">{!! $runs->links() !!}</div>
                @endif
            </div>
        </div>
