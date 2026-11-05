@extends('layouts.app')

@section('content')
    <section class="hero">
        <h1>Split the bill.<br><span class="hl">Skip the drama.</span></h1>
        <p class="lead">Snap the bill (or just type the amount), say who paid, and SplitPop does the maths nobody wants to do. No sign-up. Just a link.</p>
    </section>

    <div class="home-grid">
        <form method="POST" action="{{ route('pops.store') }}" class="card lav">
            @csrf
            <h2>Start a pop ⚡</h2>

            <label class="lbl" for="name">What's the occasion?</label>
            <input class="field" id="name" name="name" maxlength="60" required
                   placeholder="Goa trip, Flat 4B, Pizza night…" value="{{ old('name') }}">

            <span class="lbl">Pick a vibe</span>
            <div class="chips">
                @foreach (\App\Support\Look::VIBES as $vibe)
                    <label class="chip">
                        <input type="radio" name="emoji" value="{{ $vibe }}" @checked(old('emoji', '🍕') === $vibe)>
                        <span>{{ $vibe }}</span>
                    </label>
                @endforeach
            </div>

            <span class="lbl">Currency</span>
            <div class="chips">
                @foreach (\App\Support\Look::CURRENCIES as $cur)
                    <label class="chip">
                        <input type="radio" name="currency" value="{{ $cur }}" @checked(old('currency', '₹') === $cur)>
                        <span>{{ $cur }}</span>
                    </label>
                @endforeach
            </div>

            <span class="lbl">Who's in? <span class="opt">(at least 2, you can add more later)</span></span>
            <div class="friends" data-friends>
                @foreach (old('members', ['', '', '']) as $friend)
                    <input class="field" type="text" name="members[]" maxlength="40"
                           placeholder="Friend's name" value="{{ $friend }}">
                @endforeach
            </div>
            <button type="button" class="btn ghost" data-add-friend>+ another friend</button>

            <button type="submit" class="btn pink big">Make it pop 💥</button>
        </form>

        <div class="how">
            <div class="card yellow sticker s1">
                <span class="num">1</span>
                <h3>Make a pop</h3>
                <p>Name it, add your friends. Takes ten seconds.</p>
            </div>
            <div class="card mint sticker s2">
                <span class="num">2</span>
                <h3>Share the link</h3>
                <p>Anyone with the link can add bills. No accounts, no passwords.</p>
            </div>
            <div class="card sky sticker s3">
                <span class="num">3</span>
                <h3>Dump the bills</h3>
                <p>Upload a photo or only the amount. Split equally or by exact amounts.</p>
            </div>
            <div class="card pink sticker s4">
                <span class="num">4</span>
                <h3>Settle up</h3>
                <p>We find the fewest payments so everyone's even. Zero spreadsheets. 🤝</p>
            </div>
        </div>
    </div>
@endsection
