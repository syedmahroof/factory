"""
Design coverage: for each ported screen, how much of the legacy stylesheet's
vocabulary its Vue file actually uses.

A screen scores 100% when every class the Blade's own <style> block defines
appears in the Vue that replaced it — i.e. the markup was ported rather than
reinterpreted. Anything less is a screen drawn with different bones.

The blades those stylesheets were lifted from have been deleted along with the
Livewire screens they dressed, so resources/css/legacy/ is now the source rather
than a copy — docs/extract-styles.py retired with them. This still measures the
same thing: how much of each stylesheet's vocabulary the Vue actually uses.
"""
import re, pathlib

ROOT = pathlib.Path(__file__).resolve().parent.parent

# extracted stylesheet -> the Vue file(s) that should be wearing it
SCREENS = [
    ('account-form',           ['admin/accounts/AccountForm.vue']),
    ('account-list',           ['admin/accounts/AccountsIndex.vue']),
    ('investor-form',          ['admin/investors/InvestorForm.vue']),
    ('investor-list',          ['admin/investors/InvestorsIndex.vue']),
    # PortfolioStyle dresses the investor screen and the tabs it opens on.
    ('investor-portfolio',     ['admin/investors/InvestorShow.vue',
                                'admin/investors/tabs/OverviewTab.vue',
                                'admin/investors/tabs/AdvancesTab.vue']),
    ('investor-transaction',   ['admin/investors/tabs/TransactionsTab.vue']),
    ('investor-liquidity-log', ['admin/investors/tabs/LiquidityLogTab.vue']),
    ('transaction-register',   ['admin/transactions/TransactionsRegister.vue']),
    ('priority-pass',          ['admin/priority-pass/PriorityPassIndex.vue']),
    ('merchant-form',          ['admin/merchants/MerchantForm.vue']),
    ('merchant-list',          ['admin/merchants/MerchantsIndex.vue']),
    # HarborStyle dresses the merchant view AND most of its tabs — hbi- for the
    # syndicate, terms and payment entry, hbf- for the payoff letter — so it is
    # measured against all of them rather than against the shell alone.
    ('merchant-view',          ['admin/merchants/MerchantShow.vue',
                                'admin/merchants/tabs/InvestorsTab.vue',
                                'admin/merchants/tabs/AllocateTab.vue',
                                'admin/merchants/tabs/TermsTab.vue',
                                'admin/merchants/AchFeeChargeModal.vue',
                                'admin/merchants/EditParticipationModal.vue',
                                'admin/merchants/tabs/AddPaymentPanel.vue']),
    ('merchant-view-page',     ['admin/merchants/MerchantShow.vue']),
    # LedgerStyle dresses both ledgers on the merchant view, through HbLedger.
    ('merchant-ledger',        ['admin/merchants/tabs/PaymentsTab.vue', 'admin/merchants/tabs/AddPaymentPanel.vue',
                                'admin/merchants/tabs/AchFeesTab.vue',
                                'admin/merchants/tabs/InvestorsTab.vue',
                                'admin/merchants/tabs/TermsTab.vue']),
    # One panel serves the merchant tab and the investor tab, so it is measured
    # where it actually lives rather than through either wrapper.
    ('merchant-bank',          ['../components/bank/BankPanel.vue']),
    ('merchant-bank-list',     ['../components/bank/BankPanel.vue']),
    ('merchant-audit',         ['admin/merchants/tabs/AuditTab.vue']),
    ('merchant-status',        ['admin/merchants/tabs/StatusLogTab.vue']),
    ('merchant-liquidity',     ['admin/merchants/tabs/LiquidityTab.vue']),
    ('pending-investment',     ['admin/pending/PendingInvestmentsIndex.vue']),
    ('ach-run',                ['admin/ach/AchGenerate.vue']),
]

# already ported verbatim, checked by their own scripts
DONE = {
    'portfolio-activity': ['admin/dashboard/DashboardIndex.vue'],
    'actum': ['admin/ach/ActumRegister.vue', 'admin/logs/ActumLogIndex.vue', 'admin/logs/ApiLogIndex.vue'],
}

# Classes belonging to jQuery widgets the SPA does not load. selectize is replaced
# by HbMsFilter and DataTables by useTable(), so their styling hooks can never
# appear in a Vue file — counting them would make every converted screen look
# unfinished.
VENDOR = {'selectize-control', 'selectize-dropdown', 'selectize-dropdown-content', 'pp-drop',
          'selectize-input', 'optgroup-header', 'no-results', 'has-items',
          'dropdown-active', 'item', 'active', 'details-control', 'shown',
          'main-content', 'info', 'primary', 'grey', 'warn', 'tail', 'single'}

# The identity band each of these screens opened with — avatar, name, kicker, the
# chips beside it. They were standalone pages; in the SPA they are tabs under a
# hero that already carries all of it, so rendering the band again would put the
# investor's name on the screen twice. Excluded per screen rather than globally:
# it is a decision about those tabs, not about the class names.
# Priority Pass's stylesheet also dresses the merchant view's allocation modal —
# a different screen that shares it. Those classes belong to AllocateTab, so they
# are not counted against the roster.
FOREIGN = {
    'priority-pass': {'capbar', 'capnote', 'hbi-ledger'},
    # Defined in the Blade's own <style> and never used by it either — dead in
    # the original, so counting them against the port measures nothing.
    'merchant-bank': {'half'},
    # hbt-ref belongs to the all-investors register, which shows the company and
    # the entry number under the name; hbt-add is that screen's own toolbar button.
    # Both are measured against TransactionsRegister, not against this tab.
    'investor-transaction': {'hbt-ref', 'hbt-add'},
    # HarborStyle carries a participation-ledger vocabulary no blade uses — not in
    # the Livewire app either. Dead in the original, so counting it against the port
    # measures nothing.
    'merchant-view': {
        # A column width for a ledger cell no blade renders — dead in the original.
        'basis',
        'amtcell', 'hbi-feecell', 'hbi-feegrid', 'hbi-foot-note', 'hbi-ledger-card',
        'hbi-lfoot', 'hbi-lhead', 'hbi-lrow', 'lamt', 'lfee', 'lfoot-line', 'lmoney',
        'lname', 'lrate', 'lshare', 'lterms', 'lwas', 'sbasis',
    },
    'merchant-bank-list': {'hbk-type'},
}

BAND = {
    'investor-liquidity-log': {'hbg-band', 'hbg-id', 'hbg-av', 'hbg-kicker',
                               'hbg-name', 'hbg-meta', 'hbg-chip'},
    'investor-transaction': {'hbt-band', 'hbt-id', 'hbt-av', 'hbt-kicker',
                             'hbt-name', 'hbt-meta', 'hbt-chip'},
}


def classes(css_path, screen=''):
    p = ROOT / css_path
    if not p.exists():
        return set()
    body = p.read_text().split('*/\n\n', 1)[-1]
    # class selectors only, skipping state words too short to be distinctive
    return ({c for c in re.findall(r'\.([a-z][a-z0-9\-]{3,})', body)}
            - VENDOR - BAND.get(screen, set()) - FOREIGN.get(screen, set()))

# Chrome shared by every register lives in components, not in the page file — the
# page only supplies its cells. Both are read, or a converted screen scores low
# because its table markup is one directory across.
SHARED = [
    'resources/js/components/table/HbTable.vue',
    'resources/js/components/table/HbColumnPicker.vue',
    'resources/js/components/table/HbPager.vue',
    'resources/js/components/table/HbMsFilter.vue',
    'resources/js/components/table/HbLedger.vue',
    'resources/js/components/ui/HbHero.vue',
    'resources/js/components/ui/HbiModal.vue',
    'resources/js/components/merchants/TermCalendar.vue',
    'resources/js/components/ui/HbMultiSelect.vue',
    'resources/js/layouts/AdminLayout.vue',
]

def blob(vues):
    out = ''.join((ROOT / c).read_text().lower() for c in SHARED if (ROOT / c).exists())
    for v in vues:
        p = ROOT / 'resources/js/pages' / v
        if p.exists():
            out += p.read_text().lower()
    return out

rows = []
for name, vues in SCREENS:
    cls = classes(f'resources/css/legacy/{name}.css', name)
    b = blob(vues)
    used = {c for c in cls if c in b}
    rows.append((name, len(cls), len(used), vues))

for name, vues in DONE.items():
    cls = classes(f'resources/css/{name}.css')
    b = blob(vues)
    rows.append((name + ' *', len(cls), len({c for c in cls if c in b}), vues))

rows.sort(key=lambda r: (r[2] / r[1] if r[1] else 1))

print(f"{'screen stylesheet':<26}{'classes':>8}{'used':>6}{'cover':>7}   vue")
print('-' * 104)
for name, total, used, vues in rows:
    pct = (used / total * 100) if total else 100
    bar = '█' * round(pct / 10) + '·' * (10 - round(pct / 10))
    print(f"  {name:<24}{total:>8}{used:>6}{pct:>6.0f}% {bar}  {vues[0]}")
print('-' * 104)
tot = sum(r[1] for r in rows); use = sum(r[2] for r in rows)
print(f"overall: {use}/{tot} classes ({use/tot*100:.0f}%) · {sum(1 for r in rows if r[2]/r[1] > 0.9 if r[1])} of {len(rows)} screens ported")
