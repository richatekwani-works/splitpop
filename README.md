# 🍿 SplitPop: split expenses, no login

A cute, quirky Laravel app for splitting bills with friends.

- **No login.** Make a "pop" (a group) and you get a secret link like `/p/k3j9x0ab`. Share it; anyone with the link can add bills.
- **Bill photo is optional.** Upload a picture of the bill *or* just type the amount.
- **Who paid + who it's split with.** Split equally, or type exact custom amounts per person.
- **Balances + settle-up.** Shows who gets back / owes, and the fewest payments needed to even out. Tap "Paid ✅" to record a payment.
- Money is stored as integer paise/cents, so there are no rounding drifts (₹10.00 / 3 → 3.34 + 3.33 + 3.33).

## Setup (5 minutes)

You need PHP 8.2+ and Composer.

```bash
# 1. Fresh Laravel app
composer create-project laravel/laravel splitpop
cd splitpop

# 2. Copy this overlay's files INTO it (merge/overwrite when asked):
#    app/  database/  public/  resources/  routes/
#    (Windows: just drag the folders in and choose "Replace")

# 3. Database + uploads
php artisan migrate        # a fresh Laravel app uses SQLite by default, nothing to configure
php artisan storage:link   # so uploaded bill photos can be shown

# 4. Run it
php artisan serve          # open http://127.0.0.1:8000
```

Optional: set `APP_NAME=SplitPop` in `.env`.

## What's inside

| Path | What it does |
|---|---|
| `routes/web.php` | All routes (replaces the default; no auth routes) |
| `database/migrations/…create_splitpop_tables.php` | pops, members, expenses, expense_shares, settlements |
| `app/Models/*` | Pop, Member, Expense, ExpenseShare, Settlement |
| `app/Http/Controllers/*` | Pop (create/show), Member, Expense (upload + split), Settlement |
| `app/Support/Balances.php` | Pure maths: who owes whom + minimal payments |
| `app/Support/Money.php` | Paise-safe money helpers + equal split |
| `app/Support/Look.php` | Vibes, currencies, each friend's animal + colour |
| `resources/views/*` | Blade pages (layout, home, pop page) |
| `public/css/splitpop.css`, `public/js/splitpop.js` | The fun colours, chunky stickers, tiny JS |

No Tailwind/Vite/npm build is needed: it's plain CSS, so it works straight after `php artisan serve`.

## Good to know

- **The link is the password.** Anyone who has it can add or delete bills in that pop. That's the trade-off of "no login"; if you later want more safety, add an optional PIN on the pop.
- Bill photos: jpg/png/webp/gif, max 5 MB, stored in `storage/app/public/bills`.
- Friends added later only share bills created after they join.
- Create/add actions are rate-limited (`throttle`) to cut spam.

## Ideas to extend (nice for a project report)

1. Optional PIN to edit a pop · 2. Edit expense · 3. Export to PDF/CSV · 4. UPI deep link on each "Paid" button · 5. Categories + a pie chart.
