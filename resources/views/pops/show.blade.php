@extends('layouts.app')

@section('title', $pop->name)

@section('content')
    @php
        $sym = $pop->currency;
        $fmt = fn (int $cents) => \App\Support\Money::fmt($cents, $sym);
        $link = route('pops.show', $pop);
    @endphp

    <section class="card yellow banner">
        <div>
            <span class="big-emoji">{{ $pop->emoji }}</span>
            <h1>{{ $pop->name }}</h1>
            <p class="meta">{{ $members->count() }} friends · {{ $expenses->count() }} bills · {{ $fmt($total) }} spent</p>
        </div>
        <div class="share">
            <p><b>Anyone with this link can add bills 👇</b></p>
            <div class="linkbox">
                <input class="field" readonly value="{{ $link }}" onfocus="this.select()" aria-label="Share link">
                <button type="button" class="btn" data-copy="{{ $link }}">Copy link</button>
            </div>
            <p class="mini">Bookmark it! There's no login, so this link is the only way back in.</p>
        </div>
    </section>

    <div class="cols">
        <div class="col-main">

            {{-- ADD A BILL --}}
            <form method="POST" action="{{ route('expenses.store', $pop) }}" enctype="multipart/form-data" class="card mint">
                @csrf
                <h2>Add a bill 🧾</h2>

                <div class="row2">
                    <div>
                        <label class="lbl" for="title">What was it?</label>
                        <input class="field" id="title" name="title" maxlength="80" placeholder="Pizza, cab, chai…" value="{{ old('title') }}">
                    </div>
                    <div>
                        <label class="lbl" for="amount">Amount ({{ $sym }})</label>
                        <input class="field" id="amount" name="amount" type="number" step="0.01" min="0.01"
                               inputmode="decimal" required placeholder="0.00" value="{{ old('amount') }}" data-amount>
                    </div>
                </div>

                <div class="row2">
                    <div>
                        <label class="lbl" for="paid_by">Who paid?</label>
                        <select class="field" id="paid_by" name="paid_by" required>
                            @foreach ($members as $m)
                                <option value="{{ $m->id }}" @selected((string) old('paid_by') === (string) $m->id)>
                                    {{ $looks[$m->id]['emoji'] }} {{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="lbl" for="spent_on">When?</label>
                        <input class="field" id="spent_on" name="spent_on" type="date" value="{{ old('spent_on') }}" data-today>
                    </div>
                </div>

                <span class="lbl">Bill photo <span class="opt">(optional, an amount alone is totally fine)</span></span>
                <label class="drop">
                    <input type="file" name="bill" accept="image/png,image/jpeg,image/webp,image/gif">
                    <span data-drop-text>📸 Tap to add a bill photo</span>
                    <img data-bill-preview hidden alt="Bill preview">
                </label>

                <span class="lbl">How to split?</span>
                <div class="chips">
                    <label class="chip wide">
                        <input type="radio" name="split_mode" value="equal" @checked(old('split_mode', 'equal') === 'equal')>
                        <span>🍰 Equally</span>
                    </label>
                    <label class="chip wide">
                        <input type="radio" name="split_mode" value="custom" @checked(old('split_mode') === 'custom')>
                        <span>🎯 Custom amounts</span>
                    </label>
                </div>

                <div class="people" data-mode="equal">
                    @foreach ($members as $m)
                        <label class="person">
                            <input type="checkbox" name="split_with[]" value="{{ $m->id }}"
                                   @checked(in_array($m->id, (array) old('split_with', $memberIds)))>
                            <span class="avatar" style="--c: {{ $looks[$m->id]['color'] }}">{{ $looks[$m->id]['emoji'] }}</span>
                            <b>{{ $m->name }}</b>
                        </label>
                    @endforeach
                </div>

                <div class="people" data-mode="custom" hidden>
                    @foreach ($members as $m)
                        <label class="person">
                            <span class="avatar" style="--c: {{ $looks[$m->id]['color'] }}">{{ $looks[$m->id]['emoji'] }}</span>
                            <b>{{ $m->name }}</b>
                            <input class="field tiny" type="number" step="0.01" min="0" inputmode="decimal"
                                   name="custom[{{ $m->id }}]" value="{{ old('custom.' . $m->id) }}" placeholder="0" data-custom>
                        </label>
                    @endforeach
                    <p class="left" data-left></p>
                </div>

                <button type="submit" class="btn pink big">Add it 🎉</button>
            </form>

            {{-- RECEIPT PILE --}}
            <h2 class="sec">The receipts 🧾</h2>
            @forelse ($expenses as $e)
                <article class="card exp">
                    @if ($e->bill_mime)
                        <a href="{{ route('expenses.bill', [$pop, $e]) }}" target="_blank" rel="noopener">
                            <img class="thumb" src="{{ route('expenses.bill', [$pop, $e]) }}" alt="Bill photo for {{ $e->title }}" loading="lazy">
                        </a>
                    @else
                        <div class="thumb none">🧾<small>amount only</small></div>
                    @endif

                    <div class="exp-body">
                        <h3>{{ $e->title }}</h3>
                        <p class="meta">
                            {{ $looks[$e->paid_by]['emoji'] ?? '' }}
                            <b>{{ $byId->get($e->paid_by)?->name ?? 'Someone' }}</b> paid
                            · {{ $e->spent_on?->format('d M Y') }}
                        </p>
                        <div class="pills">
                            @foreach ($e->shares as $s)
                                <span class="pill" style="--c: {{ $looks[$s->member_id]['color'] ?? '#eee' }}">
                                    {{ $byId->get($s->member_id)?->name ?? '?' }} {{ $fmt($s->share_cents) }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="exp-side">
                        <div class="amt">{{ $fmt($e->amount_cents) }}</div>
                        <form method="POST" action="{{ route('expenses.destroy', [$pop, $e]) }}"
                              onsubmit="return confirm('Delete this bill?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="link">🗑️ delete</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="card empty">
                    <span class="big-emoji">👀</span>
                    <p>No bills yet. Suspiciously responsible group.</p>
                </div>
            @endforelse
        </div>

        <aside class="col-side">

            {{-- BALANCES --}}
            <section class="card sky">
                <h2>Who's where 💸</h2>
                @foreach ($members as $m)
                    @php $n = $net[$m->id] ?? 0; @endphp
                    <div class="bal">
                        <span class="avatar" style="--c: {{ $looks[$m->id]['color'] }}">{{ $looks[$m->id]['emoji'] }}</span>
                        <b>{{ $m->name }}</b>
                        @if ($n > 0)
                            <span class="tag good">gets back {{ $fmt($n) }}</span>
                        @elseif ($n < 0)
                            <span class="tag bad">owes {{ $fmt(-$n) }}</span>
                        @else
                            <span class="tag">all square ✨</span>
                        @endif
                    </div>
                @endforeach
            </section>

            {{-- SETTLE UP --}}
            <section class="card orange">
                <h2>Settle up 🤝</h2>
                @forelse ($transfers as $t)
                    <div class="xfer">
                        <div>
                            <b>{{ $byId->get($t['from'])?->name }}</b> → <b>{{ $byId->get($t['to'])?->name }}</b>
                            <div class="amt sm">{{ $fmt($t['amount']) }}</div>
                        </div>
                        <form method="POST" action="{{ route('settlements.store', $pop) }}">
                            @csrf
                            <input type="hidden" name="from_member_id" value="{{ $t['from'] }}">
                            <input type="hidden" name="to_member_id" value="{{ $t['to'] }}">
                            <input type="hidden" name="amount_cents" value="{{ $t['amount'] }}">
                            <button type="submit" class="btn small">Paid ✅</button>
                        </form>
                    </div>
                @empty
                    <p class="happy">Nobody owes anybody. Weird. Enjoy it. ☮️</p>
                @endforelse

                @if ($settlements->isNotEmpty())
                    <details class="hist">
                        <summary>Already settled ({{ $settlements->count() }})</summary>
                        @foreach ($settlements as $s)
                            <div class="xfer small">
                                <span>
                                    {{ $byId->get($s->from_member_id)?->name }} paid
                                    {{ $byId->get($s->to_member_id)?->name }} {{ $fmt($s->amount_cents) }}
                                </span>
                                <form method="POST" action="{{ route('settlements.destroy', [$pop, $s]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="link">undo</button>
                                </form>
                            </div>
                        @endforeach
                    </details>
                @endif
            </section>

            {{-- NEW FRIEND --}}
            <section class="card pink">
                <h2>New to the crew? 👋</h2>
                <form method="POST" action="{{ route('members.store', $pop) }}" class="addfriend">
                    @csrf
                    <input class="field" name="name" maxlength="40" required placeholder="Friend's name">
                    <button type="submit" class="btn small">Add</button>
                </form>
                <p class="mini">New friends only share bills you add from now on.</p>
            </section>
        </aside>
    </div>
@endsection
