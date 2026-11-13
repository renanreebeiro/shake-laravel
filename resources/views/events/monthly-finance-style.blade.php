<style>
.finance-report {
    --finance-panel: #2a1810;
    --finance-paid: #67e8a4;
    --finance-pending: #ff8989;
    display: grid;
    gap: 1rem;
    margin-top: 1rem;
    padding: clamp(.85rem, 3vw, 1.75rem);
    border-radius: 1.25rem;
    background: radial-gradient(ellipse 75% 320px at 88% 42px, #78300c, #37180e 48%, transparent), #150e0b;
    color: #fffaf4;
    overflow-wrap: anywhere;
}
.finance-report :is(h1, h2, p) { margin: 0; }
.finance-report section { border: 0; }
.finance-report h1, .finance-report h2 { font-size: 1.1rem; letter-spacing: 0; }
.finance-overview, .finance-metrics { display: grid; gap: 1rem; align-items: start; }
.finance-overview { grid-template-columns: minmax(0, 4fr) minmax(0, 5fr); }
.finance-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: stretch; }
.finance-summary { padding: .5rem 0; background: transparent; }
.finance-summary h1 { margin-bottom: .65rem; }
.finance-total {
    display: block;
    font-size: clamp(2rem, 5vw, 3.75rem);
    line-height: 1.1;
    letter-spacing: -.055em;
    text-shadow: 0 0 24px #ff6d0077;
}
.finance-report form { margin-top: .75rem; }
.finance-report label, .finance-report small, .finance-report p { color: #f0dacc; }
.finance-report label { display: block; margin-bottom: .25rem; }
.finance-filter { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
.finance-filter input { width: auto; max-width: 100%; color-scheme: dark; color: #fffaf4; background: #20130e; border-color: #9e7157; }
.finance-filter button { padding: .6rem .85rem; }
.finance-panel, .finance-ledger { min-width: 0; padding: clamp(.85rem, 2vw, 1.25rem); border-radius: .9rem; }
.finance-panel { background: var(--finance-panel); box-shadow: inset 0 1px #ff9c5133; }
.finance-panel a { color: #ffd1ae; }
.finance-agenda { list-style: none; padding: 0; margin: .75rem 0 0; }
.finance-agenda-scroll {
    max-block-size: 18rem;
    overflow-y: auto;
    scrollbar-gutter: stable;
    scrollbar-color: #ffac62 #2a1810;
    margin-top: .75rem;
    padding-inline-end: .4rem;
}
.finance-agenda-scroll:focus-visible { outline: 2px solid #ffac62; outline-offset: 3px; }
.finance-agenda-scroll .finance-agenda { margin-top: 0; }
.finance-agenda-scroll li { min-block-size: 3.6rem; }
.finance-agenda li { padding: .5rem 0; border-top: 1px solid #ffffff24; }
.finance-agenda small { display: block; }
.finance-trend { background: radial-gradient(ellipse at 83% 86%, #ff6d0029, transparent 65%), var(--finance-panel); }
.finance-plot { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .6rem; margin-top: .85rem; }
.finance-plot svg { display: block; width: 100%; height: clamp(8rem, 21vw, 13rem); overflow: visible; }
.finance-trend-line { stroke: #ffac62; filter: drop-shadow(0 0 3px #ff6d00) drop-shadow(0 0 8px #ff6d00cc); }
.finance-axis, .finance-days { display: flex; justify-content: space-between; color: #f0dacc; font-size: .75rem; }
.finance-axis { flex-direction: column; padding-bottom: 1.35rem; text-align: right; }
.finance-days { padding-top: .45rem; }
.finance-paid { color: var(--finance-paid); }
.finance-pending { color: var(--finance-pending); }
.finance-metrics strong { display: block; font-size: clamp(1.35rem, 3vw, 2rem); text-shadow: 0 0 16px color-mix(in srgb, currentColor 35%, transparent); }
.finance-allocation { display: flex; flex-wrap: wrap; gap: 1rem 2rem; align-items: center; margin-top: .75rem; }
.finance-donut {
    display: grid;
    place-items: center;
    width: 10rem;
    max-width: 100%;
    aspect-ratio: 1;
    border-radius: 50%;
    background: conic-gradient(var(--finance-paid) 0 var(--paid-share), var(--finance-pending) var(--paid-share) 100%);
    box-shadow: 0 0 18px #ff6d0029;
}
.finance-donut div { display: grid; align-content: center; width: 75%; aspect-ratio: 1; border-radius: 50%; text-align: center; padding: .4rem; background: var(--finance-panel); }
.finance-allocation dl { flex: 1; min-width: min(100%, 14rem); margin: 0; }
.finance-allocation dl div { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .35rem 1rem; padding: .55rem 0; border-bottom: 1px solid #ffffff24; }
.finance-allocation dd { margin: 0; }
.finance-ledger { background: #fffaf4; color: #221812; --finance-paid: #17633a; --finance-pending: #a42525; }
.finance-ledger p { color: #6a5547; }
.finance-table-wrap { overflow-x: auto; }
.finance-ledger table { width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums; }
.finance-ledger :is(th, td) { text-align: left; vertical-align: top; padding: .6rem; border-bottom: 1px solid #e2d4c4; }
.finance-ledger td { white-space: nowrap; }
.finance-ledger details { font-size: .85rem; font-weight: normal; }
.finance-ledger details ul { padding-inline-start: 1rem; }
@media (max-width: 680px) {
    .finance-overview, .finance-metrics { grid-template-columns: minmax(0, 1fr); }
}
</style>
