<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Store Billing — New Order</title>
<style>
  :root { --bg:#fff; --panel:#f1f5f9; --ink:#334155; --mut:#64748b; --line:#9aabc1; --pri:#337fbd; --ok:#15803d; --warn:#a64b12; --warnbg:#fffbeb; --err:#b91c1c; }
  * { box-sizing:border-box }
  body { margin:0; background:var(--bg); color:var(--ink); font:13px Arial,Helvetica,sans-serif }
  header { max-width:870px; height:50px; margin:40px auto 0; padding:0 21px; display:flex; align-items:center; justify-content:space-between; background:#1e293b; color:#fff; font-size:17px; font-weight:700; letter-spacing:.2px }
  header small { color:#94a3b8; font-size:10px; font-weight:400; letter-spacing:.2px }
  main { max-width:870px; margin:27px auto 0; padding:0 21px; display:grid; grid-template-columns:minmax(0,544px) 266px; gap:20px }
  section { margin:0 0 24px }
  h2 { margin:0 0 9px; font-size:13px; line-height:1; font-weight:700; color:#273449 }
  label { display:block; margin-bottom:3px; font-size:11px; font-weight:700; color:#475569 }
  input, select { width:100%; height:33px; padding:6px 8px; border:1px solid var(--line); border-radius:4px; background:#fff; color:var(--ink); font:12px Arial,Helvetica,sans-serif; box-shadow:0 0 0 1px #dce3ed }
  input::placeholder, select:invalid { color:#94a3b8 }
  .two { display:grid; grid-template-columns:1fr 1fr; gap:20px }
  .customer { margin-bottom:29px }
  .customer h2 { margin-bottom:0 }
  .products h2 { margin-bottom:10px }
  .table-head, .row { display:grid; grid-template-columns:minmax(0,1.8fr) 70px 80px minmax(0,1fr); gap:8px; align-items:center }
  .table-head { height:31px; padding:0 8px; border:1px solid var(--line); border-radius:5px 5px 0 0; background:#e2e8f0; font-weight:700 }
  .row { position:relative; min-height:31px; margin-top:-1px; padding:0 8px; border:1px solid var(--line); background:#fff }
  .row:last-child { border-radius:0 0 5px 5px }
  .row select, .row input { height:29px; padding:4px 0; border:0; border-radius:0; box-shadow:none; background:transparent; font-size:11px }
  .row input { padding-left:2px }
  .row .num { font-size:11px }
  .num { font-variant-numeric:tabular-nums }
  .row button.x { position:absolute; right:3px; z-index:1; display:none; padding:1px 4px; color:var(--mut); background:#fff; font-size:12px }
  .row:hover button.x, .row:focus-within button.x { display:block }
  button { min-height:31px; padding:7px 16px; border:0; border-radius:4px; font:700 13px Arial,Helvetica,sans-serif; color:#fff; background:var(--pri); cursor:pointer }
  #add { display:block; margin:9px 0 0 auto }
  .payment { margin-top:-1px }
  .payment h2 { margin-bottom:10px }
  .payment-layout { display:grid; grid-template-columns:400px 175px; gap:26px; align-items:start }
  .payment-box { padding:10px 12px 0; border:1px solid var(--line); border-radius:4px; background:var(--panel); box-shadow:0 0 0 1px #dce3ed; color:var(--mut) }
  .tot div { display:flex; justify-content:space-between; padding:3px 0; line-height:17px }
  .tot b { color:#374151 }
  .payment-box .tot div:nth-child(3) { border-bottom:2px dashed #cbd5e1; padding-bottom:8px; margin-bottom:5px }
  .payment-box label { margin:0 0 3px 6px }
  .payment-box input { height:32px }
  .balance { margin-top:12px; font-size:12px; font-weight:700; color:#475569 }
  .balance b { font-weight:700 }
  .payment-actions { padding-top:0 }
  button.go { width:100%; height:39px; background:var(--ok) }
  .payment-actions p { margin:8px 0 0; color:var(--mut); font-size:10px; line-height:13px }
  .alert { min-height:193px; padding:11px 12px; border:1px solid #f59e0b; border-radius:5px; background:var(--warnbg); color:var(--warn) }
  .alert h2 { margin:0 0 12px; color:inherit; font-size:13px }
  .alert ul { margin:0; padding-left:10px; list-style:none; font-size:11px; line-height:23px }
  .alert li::before { content:"• "; margin-left:-9px }
  #msg { padding:10px; border-radius:4px; margin-top:12px; display:none }
  .ok { background:#dcfce7; color:#166534 }
  .bad { background:#fee2e2; color:var(--err) }
  @media (max-width:920px) { header { margin:20px 20px 0 } main { margin:24px 20px 0; padding:0; grid-template-columns:minmax(0,1fr) 240px; gap:16px } .payment-layout { grid-template-columns:minmax(0,400px) minmax(145px,175px); gap:16px } }
  @media (max-width:680px) { header { margin:0; padding:0 16px; height:52px } header small { display:none } main { margin:22px 16px; display:block } .two { gap:12px } .table-head, .row { grid-template-columns:minmax(0,1.6fr) 42px 58px minmax(0,.9fr); gap:5px; padding-left:6px; padding-right:6px } .payment-layout { grid-template-columns:1fr; gap:12px } .payment-box { max-width:100% } .payment-actions { max-width:220px } .alert { margin-top:22px; min-height:0 } }
</style>
</head>
<body>
<header><span>Store Billing&nbsp; — &nbsp;New Order</span><small>WIREFRAME — layout reference only</small></header>
<main>
  <div>
    <section class="customer">
      <h2>Customer</h2>
      <div class="two">
        <div><label for="email">Email</label><input id="email" type="email" placeholder="e.g. thomas@example.com"></div>
        <div><label for="name">Name</label><input id="name" placeholder="auto-filled if email exists"></div>
      </div>
    </section>

    <section class="products">
      <h2>Products</h2>
      <div class="table-head"><span>Product</span><span>Qty</span><span>Price</span><span>Line Total</span></div>
      <div id="rows"></div>
      <button type="button" id="add">+ Add Product</button>
    </section>

    <section class="payment">
      <h2>Payment</h2>
      <div class="payment-layout">
        <div class="payment-box">
          <div class="tot">
            <div><span>Subtotal</span><b id="sub" class="num">₹0.00</b></div>
            <div><span>Tax</span><b id="tax" class="num">₹0.00</b></div>
            <div><span>Grand Total</span><b id="grand" class="num">₹0.00</b></div>
          </div>
          <label for="paid">Amount Given by Customer</label>
          <input id="paid" type="number" min="0" step="0.01" placeholder="₹250">
          <div class="tot balance"><div><span>Balance to Return:</span><b id="bal" class="num">—</b></div></div>
          <div id="chg" style="text-align:right;color:var(--mut);font-size:10px"></div>
        </div>
        <div class="payment-actions">
          <button class="go" id="bill">Generate Bill</button>
          <p>→ shows bill on page +<br>emails PDF to customer</p>
        </div>
      </div>
      <div id="msg"></div>
    </section>
  </div>

  <aside><section class="alert"><h2>⚠ Low Stock Alert</h2><ul id="low"><li>Loading…</li></ul></section></aside>
</main>

<script>
const $ = id => document.getElementById(id);
const rupees = p => '₹' + (p / 100).toFixed(2);
const paise = v => Math.round(parseFloat(v) * 100);
const api = (url, opt = {}) => fetch(url, { headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }, ...opt });
let products = [];

function lineCalc(p, qty) {          // mirrors PriceCalculator::line (integer paise, half-up tax)
  const sub = paise(p.price) * qty, bp = Math.round(parseFloat(p.tax_percent) * 100);
  const tax = Math.floor((sub * bp + 5000) / 10000);
  return { sub, tax, total: sub + tax };
}

function addRow() {
  const r = document.createElement('div'); r.className = 'row';
  r.innerHTML = `<select aria-label="Product"><option value="">+ dropdown to add product row</option>${products.map(p =>
      `<option value="${p.id}">${p.name} (${p.stock} in stock)</option>`).join('')}</select>
    <input type="number" min="1" value="1" aria-label="Quantity"><span class="num price">—</span><b class="num line">—</b><button type="button" class="x" title="Remove product" aria-label="Remove product">✕</button>`;
  r.addEventListener('input', recalc);
  r.querySelector('.x').onclick = () => { r.remove(); recalc(); };
  $('rows').appendChild(r);
}

function lines() {
  return [...document.querySelectorAll('#rows .row')].map(r => {
    const p = products.find(x => x.id == r.querySelector('select').value);
    const qty = parseInt(r.querySelector('input').value) || 0;
    return { r, p, qty };
  });
}

function recalc() {
  let sub = 0, tax = 0;
  for (const { r, p, qty } of lines()) {
    if (!p || qty < 1) { r.querySelector('.price').textContent = '—'; r.querySelector('.line').textContent = '—'; continue; }
    const c = lineCalc(p, qty); sub += c.sub; tax += c.tax;
    r.querySelector('.price').textContent = '₹' + p.price;
    r.querySelector('.line').textContent = rupees(c.total);
  }
  const grand = sub + tax;
  $('sub').textContent = rupees(sub); $('tax').textContent = rupees(tax); $('grand').textContent = rupees(grand);
  const given = $('paid').value === '' ? null : paise($('paid').value);
  if (given === null || given < grand) { $('bal').textContent = '—'; $('chg').textContent = ''; return; }
  const bal = given - grand; $('bal').textContent = rupees(bal);
  let rem = Math.floor(bal / 100), parts = [];
  for (const n of [500, 200, 100, 50, 20, 10, 5, 2, 1]) if (rem >= n) { parts.push(`${Math.floor(rem / n)}×${n}`); rem %= n; }
  $('chg').textContent = parts.join(' + ') + (bal % 100 ? ` (+ ${bal % 100} paise)` : '');
}

async function loadAll() {
  products = (await (await api('/api/products')).json()).data;
  $('rows').innerHTML = ''; addRow(); recalc();
  const low = await (await api('/api/products/low-stock')).json();
  $('low').innerHTML = low.data.length ? low.data.map(p => `<li>${p.name} — ${p.stock} units left</li>`).join('') : '<li>All good</li>';
}

$('email').addEventListener('blur', async () => {
  if (!$('email').value) return;
  const res = await api('/api/customers/lookup?email=' + encodeURIComponent($('email').value));
  if (res.ok) $('name').value = (await res.json()).data.name;
});
$('add').onclick = addRow; $('paid').oninput = recalc;

$('bill').onclick = async () => {
  const items = lines().filter(l => l.p && l.qty > 0).map(l => ({ product_id: l.p.id, quantity: l.qty }));
  const body = { customer: { email: $('email').value, name: $('name').value || null }, items };
  if ($('paid').value !== '') body.amount_paid = $('paid').value;
  const res = await api('/api/orders', { method: 'POST', body: JSON.stringify(body) });
  const json = await res.json(), m = $('msg'); m.style.display = 'block';
  if (res.ok) {
    m.className = 'ok'; m.textContent = `Bill #${json.data.id} generated. Total ${'₹' + json.data.grand_total}. Confirmation email queued.`;
    $('paid').value = ''; await loadAll();
  } else {
    m.className = 'bad';
    m.textContent = json.errors?.items?.[0]?.name
      ? json.errors.items.map(s => `${s.name}: wanted ${s.requested}, only ${s.available} left`).join('; ')
      : Object.values(json.errors || {}).flat().join(' ') || json.message;
  }
};
loadAll();
</script>
</body>
</html>
