                <div class="row">
                    <div class="col-lg-7 wa-col">
                        <div class="wa-card h-100">
                            <div class="wa-card-h">
                                <span>Import customers</span>
                                <span class="wa-chip badge-soft-primary">{{ number_format($customerStats['with_phone']) }} reachable</span>
                            </div>
                            <div class="wa-card-b">
                                <p class="wa-sub mb-3">
                                    {{ number_format($customerStats['total']) }} people in your book,
                                    {{ number_format($customerStats['with_phone']) }} with a phone number — those are the
                                    ones <b>Send a message</b> can reach.
                                </p>

                                @if (hasPermission('whatsapp_bulk', 'import'))
                                <form method="post" action="{{ route('vendor.whatsapp.customers.import') }}" enctype="multipart/form-data">
                                    @csrf
                                    <label class="wa-eyebrow d-block mb-1">Upload a spreadsheet</label>
                                    <div class="d-flex align-items-center flex-wrap mb-2" style="gap:8px;">
                                        <input type="file" name="file" class="form-control form-control-sm"
                                               style="flex:1 1 220px;min-width:0;" accept=".xlsx,.xls,.csv" required>
                                        <button class="btn btn--primary btn-sm text-nowrap" type="submit">
                                            <i class="tio-upload"></i> Import
                                        </button>
                                    </div>
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" class="custom-control-input" id="wdSendWelcome" name="send_welcome" value="1">
                                        <label class="custom-control-label" for="wdSendWelcome" style="font-size:13px;">
                                            Send a welcome message to newly imported customers
                                            <small class="text-muted d-block">Goes out in the background from your connected number, using your approved welcome template.</small>
                                        </label>
                                    </div>
                                    <div class="wa-note">
                                        Columns: <b>Name, Phone, Email, GST, Address</b> — only Name and Phone are required.
                                        <a href="{{ route('vendor.whatsapp.customers.template') }}">Download a template</a>.
                                        Duplicates (same phone) are skipped automatically.
                                    </div>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 wa-col">
                        <div class="wa-card h-100">
                            <div class="wa-card-h">Recently added</div>
                            @if ($recentCustomers->isEmpty())
                                <div class="wa-empty">
                                    <i class="tio-user-big-outlined"></i>
                                    <div class="wa-empty-t">No customers yet</div>
                                    <div class="wa-empty-s">Import a sheet to build your audience.</div>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table wa-table">
                                        <tbody>
                                            @foreach ($recentCustomers as $c)
                                                <tr>
                                                    <td>{{ $c->f_name ?: '—' }}</td>
                                                    <td class="text-muted text-right">{{ $c->phone ?: '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
