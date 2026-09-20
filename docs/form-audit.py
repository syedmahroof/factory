import re, pathlib, json

ROOT = pathlib.Path('/Users/syedmahroof/sites/ip_new')

# legacy blade(s) -> the Vue file(s) that replaced them
PAIRS = [
    ("Account form",      ["resources/views/Admin/Account/create.blade.php"],
                          ["resources/js/pages/admin/accounts/AccountForm.vue"]),
    ("Investor form",     ["resources/views/livewire/admin/investor/create.blade.php"],
                          ["resources/js/pages/admin/investors/InvestorForm.vue"]),
    ("Merchant form",     ["resources/views/livewire/admin/merchant/create.blade.php"],
                          ["resources/js/pages/admin/merchants/MerchantForm.vue"]),
    ("Add payment",       ["resources/views/livewire/admin/merchant/add-payment.blade.php"],
                          ["resources/js/pages/admin/merchants/tabs/AddPaymentPanel.vue"]),
    ("Bank create",       ["resources/views/livewire/admin/merchant/bank-create.blade.php"],
                          ["resources/js/pages/admin/merchants/tabs/BanksTab.vue"]),
    ("ACH terms",         ["resources/views/livewire/admin/merchant/terms.blade.php"],
                          ["resources/js/pages/admin/merchants/tabs/TermsTab.vue"]),
    ("Payoff letter",     ["resources/views/livewire/admin/merchant/pay-off-letter.blade.php"],
                          ["resources/js/pages/admin/merchants/tabs/PayoffTab.vue"]),
    ("Investor txn",      ["resources/views/livewire/admin/investor/transaction/create.blade.php"],
                          ["resources/js/pages/admin/investors/tabs/TransactionsTab.vue"]),
    ("Priority Pass",     ["resources/views/livewire/admin/investor/priority-pass.blade.php"],
                          ["resources/js/pages/admin/priority-pass/PriorityPassIndex.vue"]),
    ("Assign investor",   ["resources/views/livewire/admin/merchant/assign-new-investor.blade.php"],
                          ["resources/js/pages/admin/merchants/tabs/InvestorsTab.vue"]),
    ("Merchant story",    ["resources/views/livewire/admin/merchant/story.blade.php"],
                          ["resources/js/pages/admin/merchants/tabs/ContentTab.vue"]),
    ("ACH generate",      ["resources/views/livewire/admin/merchant/ach/generate.blade.php"],
                          ["resources/js/pages/admin/ach/AchGenerate.vue"]),
]

# things that are UI-only or handled differently and should not count as gaps
IGNORE = {
    'emailtaginput','search','perpage','selected','hiddencolumns','sortfield','sortdirection',
    'confirming','open','file','buttonvisible','feedirty','editflag','showall','kind',
    'investorsearch','merchantsearch','term','page','date','id','key','value','flag',
}

def norm(f):
    f = f.strip().lower()
    f = re.sub(r'^\{\{.*?\}\}$', '', f)
    f = f.split('.')[-1]
    return f

def legacy_fields(paths):
    out = set()
    for rel in paths:
        p = ROOT / rel
        if not p.exists():
            out.add(f"!! MISSING BLADE {rel}")
            continue
        s = p.read_text()
        # double-quoted HTML attribute
        for m in re.finditer(r'wire:model[a-z.0-9]*="([^"]+)"', s):
            out.add(m.group(1))
        # laravel-collective Form:: helper, single-quoted inside a PHP array
        for m in re.finditer(r"'wire:model[a-z.0-9]*'\s*=>\s*'([^']+)'", s):
            out.add(m.group(1))
        # Form::text('field', ...) / Form::select('field', ...)
        for m in re.finditer(r"Form::(?:text|select|date|number|email|password|textarea|checkbox|file)\(\s*'([^']+)'", s):
            out.add(m.group(1))
        for m in re.finditer(r'\bname="([a-z_]+)"', s):
            out.add(m.group(1))
    return out

def vue_fields(paths):
    blob = ''
    for rel in paths:
        p = ROOT / rel
        if p.exists():
            blob += p.read_text()
    return blob

print(f"{'screen':<18} {'legacy':>7} {'missing':>8}   fields not found in the Vue file")
print('-' * 100)
total_missing = 0
for name, blades, vues in PAIRS:
    lf = legacy_fields(blades)
    blob = vue_fields(vues).lower()
    missing = []
    for f in sorted(lf):
        if f.startswith('!!'):
            missing.append(f)
            continue
        n = norm(f)
        if not n or n in IGNORE or re.search(r'\{\{|\}\}', n):
            continue
        if n not in blob:
            missing.append(n)
    missing = sorted(set(missing))
    total_missing += len([m for m in missing if not m.startswith('!!')])
    print(f"{name:<18} {len(lf):>7} {len(missing):>8}   {', '.join(missing) if missing else '— none'}")
print('-' * 100)
print(f"total candidate gaps: {total_missing}")
