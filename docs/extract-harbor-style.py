#!/usr/bin/env python3
"""
Re-extract the Harbor dialog stylesheet from the Livewire app.

`resources/css/legacy/merchant-view.css` is a straight copy of the <style> block in
the Blade component below — kept a copy rather than rewritten so the Vue screens can
be diffed against the markup they replaced instead of being trusted to look the same.

Copying by hand is how it went wrong once: the file fell a generation behind and was
missing the fee bands, the cost/return chains and the queue's grouped headings, which
left those parts of the Assign investors dialog completely unstyled while every other
part of the dialog looked right. Run this instead.

    python3 docs/extract-harbor-style.py [path-to-legacy-project]

Then check the diff before committing — the point is to see what moved.
"""

import re
import sys
import textwrap
from pathlib import Path

SOURCE = 'resources/views/Admin/Merchant/Component/HarborStyle.blade.php'
TARGET = Path(__file__).resolve().parent.parent / 'resources/css/legacy/merchant-view.css'

HEADER = """/*
 * merchant-view — copied verbatim from the <style> block in
 * resources/views/Admin/Merchant/Component/HarborStyle.blade.php.
 *
 * Not rewritten as utilities: the class names are already scoped, and keeping this
 * a straight copy means the Vue screen can be diffed against the Blade it replaced
 * rather than trusted to look the same.
 *
 * Re-extract with docs/extract-harbor-style.py when the Blade moves on — an earlier
 * copy of this file had fallen a generation behind and was missing the fee bands,
 * the cost/return chains and the queue's grouped headings entirely, which left
 * those parts of the Assign investors dialog unstyled.
 */

"""

GUARD = """

/* ---------------------------------------------------------------------------
 * SPA guard — appended by docs/extract-harbor-style.py, not present in the Blade.
 *
 * Tailwind v4 generates a utility for every token it finds while scanning source
 * files, and the markup ported from the Blade carries semantic markers that look
 * exactly like utility names. `fixed` is the one that bites: the Blade uses it on
 * a fee cell to mean "this rate is not the operator's to set", Tailwind reads it
 * as `position: fixed`, and because the Blade's own stylesheet never declares a
 * position on `.feecell` there is nothing in the cascade to compete with it. The
 * cell leaves the flex row and lands on top of its neighbour, which is what put
 * two fee names on top of each other in the Assign investors dialog.
 *
 * Stated explicitly so the utility has something to lose against. Kept out of the
 * copied block above so re-extracting cannot drop it.
 * ------------------------------------------------------------------------- */
.hbi .feecell.fixed {
    position: static;
}
"""


def main() -> int:
    legacy = Path(sys.argv[1] if len(sys.argv) > 1 else Path.home() / 'sites/ip')
    source = legacy / SOURCE

    if not source.is_file():
        print(f'not found: {source}', file=sys.stderr)
        print('pass the legacy project root as the first argument', file=sys.stderr)
        return 1

    block = re.search(r'<style>(.*)</style>', source.read_text(), re.S)

    if not block:
        print(f'no <style> block in {source}', file=sys.stderr)
        return 1

    # The block is indented inside the tag; dedent so the result reads as a
    # stylesheet rather than a fragment lifted out of markup.
    css = textwrap.dedent(block.group(1)).strip('\n')

    TARGET.write_text(HEADER + css + '\n' + GUARD)
    print(f'{TARGET.relative_to(Path.cwd())}: {len(css.splitlines())} lines')

    return 0


if __name__ == '__main__':
    raise SystemExit(main())
