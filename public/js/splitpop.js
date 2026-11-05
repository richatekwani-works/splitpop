// Tiny sprinkles of interactivity. Everything still works without JS (except the fancy bits).
document.addEventListener('DOMContentLoaded', () => {
    // 1. Copy the share link
    document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const text = btn.dataset.copy;
            try {
                await navigator.clipboard.writeText(text);
            } catch (e) {
                const tmp = document.createElement('input');
                tmp.value = text;
                document.body.appendChild(tmp);
                tmp.select();
                document.execCommand('copy');
                tmp.remove();
            }
            const old = btn.textContent;
            btn.textContent = 'Copied! 🎉';
            setTimeout(() => (btn.textContent = old), 1600);
        });
    });

    // 2. "+ another friend" on the create form
    const addBtn = document.querySelector('[data-add-friend]');
    const friends = document.querySelector('[data-friends]');
    if (addBtn && friends) {
        addBtn.addEventListener('click', () => {
            if (friends.children.length >= 20) return;
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'members[]';
            input.maxLength = 40;
            input.placeholder = "Friend's name";
            input.className = 'field';
            friends.appendChild(input);
            input.focus();
        });
    }

    // 3. Equal vs custom split
    const syncMode = () => {
        const checked = document.querySelector('input[name="split_mode"]:checked');
        if (!checked) return;
        document.querySelectorAll('[data-mode]').forEach((el) => {
            el.hidden = el.dataset.mode !== checked.value;
        });
    };
    document.querySelectorAll('input[name="split_mode"]').forEach((r) => r.addEventListener('change', syncMode));
    syncMode();

    // 4. Live "how much is left to assign" for custom splits
    const amount = document.querySelector('[data-amount]');
    const left = document.querySelector('[data-left]');
    const customs = document.querySelectorAll('[data-custom]');
    const cents = (v) => Math.round(parseFloat(v || '0') * 100) || 0;
    const updateLeft = () => {
        if (!amount || !left) return;
        let sum = 0;
        customs.forEach((i) => (sum += cents(i.value)));
        const diff = cents(amount.value) - sum;
        left.classList.toggle('ok', diff === 0 && sum > 0);
        if (sum === 0) left.textContent = 'Type what each person owes 🎯';
        else if (diff === 0) left.textContent = 'Perfect, all assigned ✅';
        else if (diff > 0) left.textContent = (diff / 100).toFixed(2) + ' still to assign';
        else left.textContent = (Math.abs(diff) / 100).toFixed(2) + ' too much!';
    };
    if (amount) amount.addEventListener('input', updateLeft);
    customs.forEach((i) => i.addEventListener('input', updateLeft));
    updateLeft();

    // 5. Bill photo preview
    const file = document.querySelector('input[type="file"][name="bill"]');
    const preview = document.querySelector('[data-bill-preview]');
    const dropText = document.querySelector('[data-drop-text]');
    if (file && preview) {
        file.addEventListener('change', () => {
            const f = file.files && file.files[0];
            if (!f) {
                preview.hidden = true;
                if (dropText) dropText.textContent = '📸 Tap to add a bill photo';
                return;
            }
            preview.src = URL.createObjectURL(f);
            preview.hidden = false;
            if (dropText) dropText.textContent = '✅ ' + f.name;
        });
    }

    // 6. Default the date to the visitor's today (their timezone, not the server's)
    const date = document.querySelector('[data-today]');
    if (date && !date.value) {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        date.value = d.toISOString().slice(0, 10);
    }
});
