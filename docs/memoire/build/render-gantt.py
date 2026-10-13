"""Render Figure III.16 (project Gantt chart) from dated evidence only.
Each bar runs from the first to the last day with a commit for that activity (git log), except the
first row, documented in docs/pfe/11-development-history.md §2.5 (pre-git sessions of 04-05/10/2026).
Run: python -I render-gantt.py <out.png>
"""
import sys
from datetime import date, timedelta
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import matplotlib.dates as mdates

NAVY, LIGHT, BAND, GRID = '#1F3864', '#8EAADB', '#EAF2FB', '#BFBFBF'

# (section, task, first day, last day, postponed?)
D = lambda d: date(2026, 10, d)
ROWS = [
    ('Development', 'Local setup, debugging and hardening', D(4), D(5), False),
    ('Development', 'Payment hardening (Chargily)', D(6), D(6), False),
    ('Development', 'Account and administration features', D(6), D(6), False),
    ('Development', 'Mobile token API (Sanctum)', D(7), D(7), False),
    ('Development', 'Flutter client: authentication (postponed)', D(9), D(9), True),
    ('Development', 'Website claims aligned with the code', D(11), D(11), False),
    ('Development', 'Continuous-integration workflows', D(11), D(11), False),
    ('Testing', 'Automated tests added or extended', D(6), D(11), False),
    ('Documentation', 'Technical documentation (docs/pfe)', D(5), D(13), False),
    ('Thesis', 'Planning and verification', D(7), D(10), False),
    ('Thesis', 'Chapter II', D(10), D(11), False),
    ('Thesis', 'Chapters III and IV', D(11), D(11), False),
    ('Thesis', 'Front matter, assembly and revisions', D(11), D(13), False),
]

plt.rcParams['font.family'] = 'Times New Roman'
n = len(ROWS)
fig, ax = plt.subplots(figsize=(8, 0.40 * n + 1.1), dpi=300)
start, end = D(4), D(14)

# Section bands (alternating) + bold section names on the left.
i = 0
shade = True
while i < n:
    j = i
    while j < n and ROWS[j][0] == ROWS[i][0]:
        j += 1
    if shade:
        ax.axhspan(i - 0.5, j - 0.5, xmin=0, xmax=1, color=BAND, zorder=0)
    ax.text(-0.86, (i + j - 1) / 2, ROWS[i][0], transform=ax.get_yaxis_transform(), rotation=90 if j - i > 2 else 0,
            ha='center', va='center', fontsize=13.5, fontweight='bold', color=NAVY)
    if i:
        ax.axhline(i - 0.5, color=GRID, lw=0.6, zorder=1)
    shade = not shade
    i = j

for k, (_, task, a, b, post) in enumerate(ROWS):
    left = mdates.date2num(a)
    width = (b - a).days + 1
    ax.barh(k, width, left=left, height=0.62, color=LIGHT if post else NAVY,
            edgecolor=NAVY, lw=0.8, zorder=3)

ax.set_yticks(range(n))
ax.set_yticklabels([r[1] for r in ROWS], fontsize=12.5)
ax.invert_yaxis()
ax.set_ylim(n - 0.5, -0.5)
ax.set_xlim(mdates.date2num(start), mdates.date2num(end))
days = [start + timedelta(d) for d in range((end - start).days)]
ax.set_xticks([mdates.date2num(d) + 0.5 for d in days])
ax.set_xticklabels([d.strftime('%d') for d in days], fontsize=11.5)
ax.set_xticks([mdates.date2num(d) for d in days] + [mdates.date2num(end)], minor=True)
ax.tick_params(axis='x', which='major', length=0)
ax.tick_params(axis='y', length=0)
ax.grid(axis='x', which='minor', color=GRID, lw=0.6, zorder=1)
ax.xaxis.tick_top()
for s in ax.spines.values():
    s.set_color(GRID)
ax.set_xlabel('October 2026', fontsize=12.5, labelpad=6)
ax.xaxis.set_label_position('top')
fig.subplots_adjust(left=0.50, right=0.985, top=0.88, bottom=0.03)
fig.savefig(sys.argv[1], facecolor='white')
print('gantt written:', sys.argv[1])
