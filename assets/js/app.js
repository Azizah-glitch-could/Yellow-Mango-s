/* Yellow Mango's: JavaScript frontend (Tahap 3).
   Produk, stok, pesanan, dan admin datang dari server PHP + MySQL lewat folder api/.
   Di browser hanya disimpan: isi keranjang dan daftar nomor pesanan milik perangkat ini. */
(()=>{
'use strict';
let CFG={wa:'',minStock:5,mdr:.007,promo:{code:'',pct:0},promoEnd:0,promoDays:3,hours:'',simulate:true,ship:{gosend:{name:'',fee:0,eta:''},grab:{name:'',fee:0,eta:''}},branches:[]};
let PRODUCTS=[],COMBO={id:'combo',name:'Mango Fresh Combo',price:35000,normal:38000,items:['mangga','salad']};
const REVIEWS=[['Alya','Mahasiswa','Segar banget buat siang-siang panas di Makassar. Pesannya gampang.',5],['Rizky','Pekerja kantoran','Dipesan lewat website, 40 menit sudah sampai dan tidak tumpah.',5],['Dinda','Pelajar','Porsinya pas, rasanya fruity. Bundle combo lebih hemat.',4]];
const STG={kirim:['Order diterima','Dipacking','Diserahkan ke kurir','Dalam perjalanan','Delivered'],ambil:['Order diterima','Sedang diproses','Siap diambil','Selesai']};
const PAYS={qris:['QRIS','Scan dari m-banking atau e-wallet apa pun. Metode utama.'],gopay:['GoPay','Bayar lewat aplikasi GoPay.'],shopeepay:['ShopeePay','Bayar lewat aplikasi ShopeePay.'],dana:['DANA','Bayar lewat aplikasi DANA.'],transfer:['Transfer Bank','Transfer ke rekening toko.'],va:['Virtual Account','Bayar ke nomor VA, status dicek otomatis.']};
const NAV=[['beranda','Beranda'],['menu','Menu'],['promo','Promo'],['cabang','Cabang'],['pesanan','Pesanan'],['blueprint','Blueprint'],['admin','Admin']];
/* ====== STATE ====== */
const KEY='ym_state_v2';
const $=(s,e=document)=>e.querySelector(s);
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const rp=n=>'Rp'+Math.round(n).toLocaleString('id-ID');
const tm=t=>new Date(t).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'}).replace(':','.');
const P=id=>PRODUCTS.find(p=>p.id===id);
const eff=p=>p.promo||p.price;
const S={cart:[],my:[]};
function load(){try{const r=JSON.parse(localStorage.getItem(KEY)||'{}');S.cart=Array.isArray(r.cart)?r.cart:[];S.my=Array.isArray(r.my)?r.my:[]}catch(e){}}
function save(){try{localStorage.setItem(KEY,JSON.stringify({cart:S.cart,my:S.my}))}catch(e){}}
load();
const U={route:(location.hash||'#beranda').slice(1)||'beranda',q:'',cat:'all',sort:'terlaris',price:'all',view:0,tab:'2',cur:null,pq:1,oc:{},list:[],admin:null,adminErr:'',lastUser:'',skew:0,busy:false,perr:'',user:null,ub:[],af:{},aerr:{},atab:'pesanan',next:null,
 co:{step:1,err:{},f:{nama:'',wa:'',email:'',alamat:'',catatan:'',del:'kirim',courier:'gosend',branch:0,pay:'qris',promo:'',promoOk:false,agree:false}}};
const nowS=()=>Date.now()+U.skew;
/* ====== KOMUNIKASI DENGAN SERVER (api/*.php) ====== */
async function api(path,data,form){
  const o={method:data?'POST':'GET',headers:{'X-Requested-With':'fetch'},credentials:'same-origin'};
  if(data){if(form)o.body=data;else{o.headers['Content-Type']='application/json';o.body=JSON.stringify(data)}}
  let r,j;
  try{r=await fetch('api/'+path,o);j=await r.json()}catch(e){throw {net:true,msg:'Server tidak dapat dihubungi. Pastikan Apache dan MySQL menyala, database sudah di-import, dan website dibuka lewat alamat http://localhost/... (bukan klik dua kali file).'}}
  if(j._now)U.skew=j._now-Date.now();
  if(!r.ok)throw {status:r.status,msg:j.error||'Terjadi kesalahan.',field:j.field};
  return j;
}
async function refreshProducts(){try{PRODUCTS=(await api('bootstrap.php')).products}catch(e){}}
const myRef=id=>S.my.find(x=>x.id===id);
const waOf=id=>(myRef(id)||{}).wa||'';
async function loadOrder(id){
  const r=myRef(id);if(!r&&!U.user)return null;
  try{const d=await api('order_get.php',{id,wa:waOf(id)});U.oc[id]=d.order;return d.order}
  catch(e){if(e.status===404){if(r){S.my=S.my.filter(x=>x.id!==id);save()}delete U.oc[id]}else if(e.net)throw e;return null}
}
async function loadList(){
  if(!S.my.length){U.list=[];return}
  const d=await api('order_list.php',{orders:S.my});U.list=d.orders;d.orders.forEach(o=>U.oc[o.id]=o);
  const ok=new Set(d.orders.map(o=>o.id));if(S.my.some(x=>!ok.has(x.id))){S.my=S.my.filter(x=>ok.has(x.id));save()}
}
function prefill(){const u=U.user,f=U.co.f;if(!u)return;if(!f.nama)f.nama=u.nama;if(!f.wa)f.wa=u.hp;if(!f.email)f.email=u.email;if(!f.alamat&&u.alamat)f.alamat=u.alamat}
function setUser(d){U.user=d.user;U.ub=d.orders||[];prefill()}
async function refreshUser(){try{const d=await api('user_me.php');if(d.user)setUser(d);else{U.user=null;U.ub=[]}}catch(e){if(e.net)throw e}}
const stockOf=id=>{const p=P(id);return p?p.stock:0};
const avail=id=>id==='combo'?Math.min(...COMBO.items.map(stockOf)):stockOf(id);
function stockTxt(id){const a=avail(id);return a<=0?['out','Out of Stock']:a<=CFG.minStock?['low','Stok menipis, sisa '+a]:['ok','Tersedia, sisa '+a]}
const lines=()=>S.cart.filter(c=>c.id==='combo'||P(c.id)).map(c=>{if(c.id==='combo')return {id:'combo',name:COMBO.name,unit:COMBO.price,qty:c.qty};const p=P(c.id);return {id:c.id,name:p.short,unit:eff(p),qty:c.qty}});
const count=()=>S.cart.reduce((a,c)=>a+c.qty,0);
function T(){const sub=lines().reduce((a,l)=>a+l.unit*l.qty,0),f=U.co.f;const disc=f.promoOk?Math.round(sub*CFG.promo.pct/100):0;const ship=f.del==='kirim'?CFG.ship[f.courier].fee:0;return {sub,disc,ship,total:sub-disc+ship}}
/* ====== ILUSTRASI (pengganti foto; isi kolom foto di tabel produk) ====== */
function art(p,alt){
  const a=esc(alt||p.alt);
  if(p.img)return `<img src="${esc(p.img)}" alt="${a}" loading="lazy">`;
  const sh='<ellipse cx="100" cy="190" rx="56" ry="7" fill="#000" opacity=".12"/>';
  if(p.art==='cup')return `<svg viewBox="0 0 200 200" role="img" aria-label="${a}">${sh}<rect x="104" y="12" width="9" height="58" rx="4.5" fill="#2E6B34" transform="rotate(9 108 60)"/><path d="M56 72h88l-10 106q-1 8-9 8H75q-8 0-9-8z" fill="${p.col}"/><path d="M56 72h88l-3 32H59z" fill="#fff" opacity=".35"/><circle cx="86" cy="120" r="7" fill="#fff" opacity=".35"/><circle cx="116" cy="148" r="5" fill="#fff" opacity=".3"/><rect x="50" y="62" width="100" height="14" rx="7" fill="#1D2A10"/></svg>`;
  if(p.art==='bowl')return `<svg viewBox="0 0 200 200" role="img" aria-label="${a}">${sh}<circle cx="70" cy="98" r="18" fill="#E63946"/><circle cx="104" cy="84" r="20" fill="#FFB703"/><circle cx="136" cy="100" r="17" fill="#7CB518"/><circle cx="88" cy="114" r="16" fill="#FF8FA3"/><circle cx="120" cy="118" r="15" fill="#F77F00"/><path d="M28 112h144q-5 50-42 66H70q-37-16-42-66z" fill="#fff" stroke="#1D2A10" stroke-width="3"/><path d="M40 128h120" stroke="${p.col}" stroke-width="6" stroke-linecap="round" opacity=".7"/></svg>`;
  return `<svg viewBox="0 0 200 200" role="img" aria-label="${a}">${sh}<path d="M52 118q0-56 48-56t48 56z" fill="#fff"/><circle cx="84" cy="92" r="8" fill="#F77F00"/><circle cx="116" cy="84" r="8" fill="#E63946"/><circle cx="100" cy="106" r="7" fill="#7CB518"/><path d="M34 116h132q-5 48-40 62H74q-35-14-40-62z" fill="${p.col}" stroke="#1D2A10" stroke-width="3"/></svg>`;
}
const comboArt=()=>art(P('mangga'))+art(P('salad'));
function toast(m){const t=$('#toast');t.textContent=m;t.hidden=false;clearTimeout(toast.t);toast.t=setTimeout(()=>t.hidden=true,2600)}
function stars(r){return r?`<span class="rate">★ <b>${String(r).replace('.',',')}</b>/5</span>`:''}
function tbl(h,rows){return `<div class="tw"><table><thead><tr>${h.map(x=>`<th>${x}</th>`).join('')}</tr></thead><tbody>${rows.map(r=>`<tr>${r.map(c=>`<td>${c}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`}
const chain=a=>`<div class="chain">${a.map((x,i)=>(i?'<i>→</i>':'')+`<span>${x}</span>`).join('')}</div>`;
/* ====== TAMPILAN ====== */
function card(p){
  const [sc,st]=stockTxt(p.id);
  const bd=p.tags.map(t=>`<span class="bdg ${t==='Promo'?'promo':t==='Best Seller'?'best':'new'}">${t}</span>`).join('');
  return `<article class="card"><button class="ph" style="background:${p.bg}" data-a="go" data-r="p-${p.id}" aria-label="Lihat detail ${esc(p.short)}">${art(p)}<span class="badges">${bd}</span></button>
  <div class="bd"><h3>${esc(p.short)}</h3>${p.soon?'<p class="mute">Segera hadir. Harga dan stok belum ditetapkan.</p>':`<div class="row">${stars(p.rating)}<span class="rate">(${p.rev} ulasan contoh)</span></div><div class="price">${p.promo?`<s>${rp(p.price)}</s>`:''}${rp(eff(p))}</div><div class="stock ${sc}">${st}</div>`}
  <div class="row mt"><button class="btn sm" data-a="go" data-r="p-${p.id}">Detail</button>${p.soon?'':`<button class="btn pri sm" data-a="add" data-id="${p.id}" ${avail(p.id)<=0?'disabled':''}>Tambah ke Keranjang</button>`}</div></div></article>`;
}
function list(){
  let a=PRODUCTS.filter(p=>(U.cat==='all'||p.cat===U.cat)&&(!U.q||(p.name+' '+p.kw).toLowerCase().includes(U.q.toLowerCase())));
  if(U.price==='low')a=a.filter(p=>p.price&&eff(p)<=18000);
  if(U.price==='high')a=a.filter(p=>p.price&&eff(p)>18000);
  const s=U.sort;a.sort((x,y)=>s==='terlaris'?y.sold-x.sold:s==='terbaru'?y.added-x.added:(eff(x)||1e9)-(eff(y)||1e9));
  return a;
}
function gridHTML(){const a=list();return a.length?a.map(card).join(''):`<div class="empty" style="grid-column:1/-1"><h3>Menu tidak ditemukan</h3><p>Ubah kata kunci atau filter, lalu coba lagi.</p><button class="btn" data-a="reset">Reset filter</button></div>`}
function comboBanner(){const [sc,st]=stockTxt('combo');return `<div class="combo"><div class="arts" style="background:none">${comboArt()}</div><div><h3>${COMBO.name}</h3><p>1 Smoothies Mangga + 1 Salad Buah. ${rp(COMBO.normal)} jika dibeli terpisah, jadi ${rp(COMBO.price)}. Hemat ${rp(COMBO.normal-COMBO.price)}.</p><p style="font-size:.85rem">${st}</p></div><button class="btn pri" data-a="add" data-id="combo" ${avail('combo')<=0?'disabled':''}>Ambil Paket ${rp(COMBO.price)}</button></div>`}
function vHome(){
  const m=P('mangga');
  return `<section class="hero"><div><span class="promo-tag">Promo diskon langsung ${CFG.promoDays} hari untuk Smoothies Mangga</span><h1>Smoothies mangga segar, dipesan dari satu website.</h1><p class="lead">Menu, harga, cabang, dan pembayaran ada di satu tempat. Tidak perlu lagi bertanya lewat DM. Cocok untuk siang panas di Makassar.</p><div class="cta"><button class="btn pri" data-a="go" data-r="menu">Lihat Menu</button><button class="btn" data-a="go" data-r="p-mangga">Pesan Smoothies Mangga</button></div></div><div class="art">${art(m)}</div></section>
  <div class="facts"><div><b>Tiba ≤ 60 menit</b><span>setelah pembayaran terkonfirmasi, area Makassar</span></div><div><b>QRIS dan e-wallet</b><span>GoPay, ShopeePay, DANA, transfer, VA</span></div><div><b>Ambil di 3 outlet</b><span>Pettarani, Boulevard, Hertasning</span></div></div>
  <section class="blk"><div class="sec-h"><div><h2>Menu andalan</h2><p>Tiga produk utama dengan stok yang selalu terlihat. Jika habis, statusnya langsung Out of Stock.</p></div><button class="btn" data-a="go" data-r="menu">Semua menu</button></div><div class="grid">${PRODUCTS.slice(0,3).map(card).join('')}</div>${comboBanner()}</section>
  <section class="blk"><div class="sec-h"><div><h2>Cara pesan</h2></div></div><ol class="steps"><li><b>Pilih menu</b><span>Lihat harga, stok, dan deskripsi produk.</span></li><li><b>Isi data</b><span>Nama, nomor HP, dan alamat.</span></li><li><b>Pilih pengiriman</b><span>Kurir instan atau ambil di outlet.</span></li><li><b>Bayar</b><span>QRIS sebagai pilihan utama.</span></li><li><b>Lacak pesanan</b><span>Status berubah sampai tiba atau siap diambil.</span></li></ol></section>
  <section class="blk"><div class="three"><div><h3>Informasi lengkap</h3><p>Menu, harga, promo, lokasi cabang, dan jam buka tidak lagi tersebar di banyak media.</p></div><div><h3>Bayar tanpa ribet</h3><p>Pilih QRIS atau e-wallet yang sudah ada di HP kamu. Status pembayaran jelas: menunggu, berhasil, atau gagal.</p></div><div><h3>Bisa dipantau</h3><p>Dari dipacking sampai diterima, statusnya terlihat di halaman Pesanan Saya.</p></div></div></section>
  <section class="blk"><blockquote>Yellow Mango's mengantar smoothies dan salad buah tetap segar, utuh, dan tidak tumpah ke alamat kamu di Makassar paling lama 60 menit setelah pembayaran terkonfirmasi.<small>Janji layanan logistik, LK Pertemuan VI</small></blockquote></section>`;
}
function vMenu(){
  return `<div class="sec-h"><div><h1 style="font-size:clamp(2rem,5vw,3rem)">Menu</h1><p>Yellow Mango's / Menu. Smoothies dan Dessert.</p></div></div>
  <div class="filters" role="search"><input id="q" type="search" placeholder="Cari menu, misalnya mangga" aria-label="Cari menu" value="${esc(U.q)}">
  <select id="fcat" aria-label="Kategori"><option value="all">Semua kategori</option><option value="smoothies" ${U.cat==='smoothies'?'selected':''}>Smoothies</option><option value="dessert" ${U.cat==='dessert'?'selected':''}>Dessert</option></select>
  <select id="fprice" aria-label="Filter harga"><option value="all">Semua harga</option><option value="low" ${U.price==='low'?'selected':''}>Sampai Rp18.000</option><option value="high" ${U.price==='high'?'selected':''}>Di atas Rp18.000</option></select>
  <select id="fsort" aria-label="Urutkan"><option value="terlaris">Terlaris</option><option value="terbaru" ${U.sort==='terbaru'?'selected':''}>Terbaru</option><option value="termurah" ${U.sort==='termurah'?'selected':''}>Harga terendah</option></select></div>
  <div class="grid" id="grid">${gridHTML()}</div>${comboBanner()}`;
}
function vProduct(id){
  const p=P(id);if(!p)return vMenu();
  const [sc,st]=stockTxt(p.id);const hasCombo=['mangga','salad'].includes(p.id);
  const views=[['Foto utama','scale(1)'],['Foto detail','scale(1.7) translateY(8%)'],['Suasana santai','scale(.8)']];
  const v=views[U.view]||views[0];
  const related=PRODUCTS.filter(x=>x.id!==p.id);
  const cross=p.id==='mangga'?P('salad'):p.id==='salad'?P('mangga'):null;
  return `<div class="crumb"><button data-a="go" data-r="menu">Menu</button> / ${p.cat==='smoothies'?'Smoothies':'Dessert'} / ${esc(p.short)}</div>
  <div class="pd"><div class="gal"><div class="main" style="background:${U.view===2?'var(--tint)':p.bg}"><div style="width:100%;height:100%;transform:${v[1]};transform-origin:50% 40%;transition:transform .25s">${art(p)}</div></div>
  <div class="thumbs">${views.map((x,i)=>`<button data-a="view" data-i="${i}" aria-pressed="${U.view===i}">${x[0]}</button>`).join('')}</div><p class="mute" style="font-size:.85rem;margin-top:8px">Ilustrasi sementara. Isi kolom foto di tabel produk untuk memakai foto asli.</p></div>
  <div><div class="badges" style="position:static;margin-bottom:10px">${p.tags.map(t=>`<span class="bdg ${t==='Promo'?'promo':t==='Best Seller'?'best':'new'}">${t}</span>`).join('')}</div>
  <h1 style="font-size:clamp(1.8rem,4vw,2.6rem)">${esc(p.name)}</h1>
  ${p.soon?`<p style="margin-top:14px">${esc(p.brief)}</p>`:`<div class="row" style="margin-top:8px">${stars(p.rating)}<span class="rate">${p.rev} ulasan contoh</span></div>
  <div class="price" style="font-size:1.8rem;margin:12px 0">${p.promo?`<s>${rp(p.price)}</s>`:''}${rp(eff(p))}</div>
  ${p.promo?`<p class="mute">Promo diskon langsung, hemat ${rp(p.price-p.promo)}. Berlaku ${CFG.promoDays} hari.</p>`:''}
  <p style="margin:12px 0">${esc(p.brief)}</p>
  <ul class="spec"><li><b>Varian</b>Regular</li><li><b>Ukuran</b>${esc(p.size)}</li><li><b>Kategori</b>${p.cat==='smoothies'?'Smoothies':'Dessert Buah'}</li><li><b>Stok</b><span class="stock ${sc}">${st}</span></li></ul>
  <div class="row" style="margin-top:18px"><div class="qty" aria-label="Jumlah"><button data-a="pq" data-d="-1" aria-label="Kurangi">−</button><span id="pq">1</span><button data-a="pq" data-d="1" aria-label="Tambah">+</button></div>
  <button class="btn pri" data-a="buy" data-id="${p.id}" ${avail(p.id)<=0?'disabled':''}>Pesan Sekarang</button><button class="btn" data-a="addq" data-id="${p.id}" ${avail(p.id)<=0?'disabled':''}>Tambah ke Keranjang</button></div>`}</div></div>
  ${p.soon?'':`<section class="blk"><div class="two"><div><h2 style="font-size:1.4rem">Cerita produk</h2><p style="margin-top:10px">${esc(p.long)}</p><p class="mute" style="margin-top:10px">Kata kunci rasa: ${p.power.join(', ')}.</p></div>
  ${hasCombo?`<div class="panel"><h3>Lebih hemat dengan paket</h3><p class="mute" style="margin:6px 0 12px">${COMBO.name}: 1 Smoothies Mangga + 1 Salad Buah. ${rp(COMBO.normal)} jadi ${rp(COMBO.price)}.</p><button class="btn pri" data-a="add" data-id="combo" ${avail('combo')<=0?'disabled':''}>Ambil Paket ${rp(COMBO.price)}</button>${cross?`<p style="margin-top:14px" class="mute">Cocok dipasangkan dengan <button class="btn link" data-a="go" data-r="p-${cross.id}">${cross.short}</button>.</p>`:''}</div>`:''}</div></section>
  <section class="blk"><h2 style="font-size:1.4rem">Ulasan pelanggan</h2><p class="mute" style="margin:4px 0 8px">Ulasan di bawah adalah contoh untuk prototype.</p>${REVIEWS.map(r=>`<div class="rev"><b>${r[0]}</b> <span class="mute">${r[1]}</span> <span class="rate">${'★'.repeat(r[3])}</span><p>${r[2]}</p></div>`).join('')}</section>`}
  <section class="blk"><h2 style="font-size:1.4rem;margin-bottom:16px">Produk lainnya</h2><div class="grid">${related.map(card).join('')}</div></section>`;
}
function ptime(){const ms=Math.max(0,CFG.promoEnd-Date.now());return `${Math.floor(ms/864e5)} hari ${Math.floor(ms%864e5/36e5)} jam ${Math.floor(ms%36e5/6e4)} menit`}
function vPromo(){
  const m=P('mangga');
  return `<div class="sec-h"><div><h1 style="font-size:clamp(2rem,5vw,3rem)">Promo</h1><p>Promo dibuat terbatas supaya margin tetap terjaga.</p></div></div>
  <div class="two"><div class="panel"><span class="pill warn">Berakhir dalam ${ptime()}</span><h2 style="margin:12px 0 6px">Smoothies Mangga ${rp(m.promo)}</h2><p class="mute"><s>${rp(m.price)}</s> diskon langsung ${rp(m.price-m.promo)} selama ${CFG.promoDays} hari.</p><div class="sp"></div><button class="btn pri" data-a="go" data-r="p-mangga">Pesan sekarang</button></div>
  <div class="panel"><h2 style="margin-bottom:6px">Kode promo ${CFG.promo.pct}%</h2><p class="mute">Masukkan kode ini di langkah Pengiriman saat checkout. Kode ini contoh untuk prototype.</p><p style="margin:14px 0"><code style="font:800 1.4rem var(--display);background:var(--tint);padding:6px 14px;border-radius:10px">${CFG.promo.code}</code></p><button class="btn" data-a="copy" data-t="${CFG.promo.code}">Salin kode</button></div></div>
  ${comboBanner()}`;
}
function vCabang(){
  return `<div class="sec-h"><div><h1 style="font-size:clamp(2rem,5vw,3rem)">Cabang dan bantuan</h1><p>Pilih "Ambil di outlet" saat checkout. Pesanan siap sekitar 15 menit setelah dibayar.</p></div></div>
  <div class="grid">${CFG.branches.map(b=>`<div class="card"><div class="bd"><h3>${b.n}</h3><p class="mute">${b.a}</p><p><b>Jam buka:</b> ${CFG.hours}</p><button class="btn sm mt" data-a="go" data-r="menu">Pesan, ambil di sini</button></div></div>`).join('')}</div>
  <section class="blk two"><div><h2 style="font-size:1.4rem;margin-bottom:10px">Kebijakan retur dan refund</h2><p class="mute" style="margin-bottom:12px">Produk segar dibuat sesuai pesanan. Ajukan retur paling lama 1 jam setelah pesanan diterima, dengan foto atau video.</p>${tbl(['Kondisi','Diterima?','Solusi'],[['Barang rusak','Ya','Dibuat ulang atau refund'],['Salah barang atau varian','Ya','Dibuat ulang, refund bila stok habis'],['Tidak sesuai deskripsi','Ya, setelah verifikasi','Dibuat ulang atau refund sebagian/penuh'],['Berubah pikiran','Tidak','Tidak ada retur']])}</div>
  <div class="panel"><h2 style="font-size:1.4rem;margin-bottom:10px">Butuh bantuan?</h2><p class="mute" style="margin-bottom:14px">Hubungi admin lewat WhatsApp. Sebutkan nomor order kamu.</p><p style="margin-bottom:14px">Nomor admin: <b>+${CFG.wa}</b> (ganti di api/config.php)</p><a class="btn pri" href="https://wa.me/${CFG.wa}" target="_blank" rel="noopener">Chat WhatsApp admin</a><div class="sp"></div><p class="mute">Pembatalan: pesanan yang belum dibayar bisa dibatalkan di halaman pembayaran. Pesanan yang sudah diproses tidak dapat dibatalkan.</p></div></section>`;
}
/* ====== KERANJANG ====== */
function lineHTML(l,ctl=true){
  const th=l.id==='combo'?`<div class="th" style="background:${P('mangga').bg}">${art(P('mangga'))}</div>`:`<div class="th" style="background:${P(l.id).bg}">${art(P(l.id))}</div>`;
  return `<div class="line">${th}<div><b>${esc(l.name)}</b><div class="mute" style="font-size:.88rem">${rp(l.unit)} per item</div>${ctl?`<div class="row" style="margin-top:4px"><div class="qty"><button data-a="qty" data-id="${l.id}" data-d="-1" aria-label="Kurangi ${esc(l.name)}">−</button><span>${l.qty}</span><button data-a="qty" data-id="${l.id}" data-d="1" aria-label="Tambah ${esc(l.name)}">+</button></div><button class="btn link" data-a="rm" data-id="${l.id}">Hapus</button></div>`:`<div class="mute" style="font-size:.88rem">Jumlah ${l.qty}</div>`}</div><b>${rp(l.unit*l.qty)}</b></div>`;
}
function drawer(){
  const L=lines(),t=L.reduce((a,l)=>a+l.unit*l.qty,0);
  $('#layer').innerHTML=`<div class="scrim" data-a="close"></div><aside class="drawer" role="dialog" aria-modal="true" aria-label="Keranjang belanja"><div class="row" style="justify-content:space-between;margin-bottom:8px"><h2 style="font-size:1.5rem">Keranjang</h2><button class="ibtn" data-a="close">Tutup</button></div>
  <div class="list">${L.length?L.map(l=>lineHTML(l)).join(''):`<div class="empty"><h3>Keranjang masih kosong</h3><p>Pilih smoothies atau salad buah dari menu.</p><button class="btn pri" data-a="go" data-r="menu">Lihat menu</button></div>`}</div>
  ${L.length?`<div class="row" style="justify-content:space-between;margin:14px 0"><span>Subtotal</span><b class="price">${rp(t)}</b></div><button class="btn pri" style="width:100%" data-a="checkout">Lanjut ke Checkout</button>`:''}</aside>`;
}
function closeLayer(){$('#layer').innerHTML=''}
function addCart(id,q=1){
  const a=avail(id);if(a<=0){toast('Out of Stock');return false}
  const c=S.cart.find(x=>x.id===id);const n=Math.min(a,(c?c.qty:0)+q);
  if(c)c.qty=n;else S.cart.push({id,qty:n});
  if((c?c.qty:n)>=a&&n===a)toast('Jumlah dibatasi sesuai stok ('+a+')');else toast('Ditambahkan ke keranjang');
  save();badge();return true;
}
function badge(){$('#cartCnt').textContent=count()}
/* ====== CHECKOUT (LK 4: Cart > Data > Pengiriman > Pembayaran > Konfirmasi) ====== */
const STEPS=['Keranjang','Data','Pengiriman','Pembayaran','Konfirmasi'];
function fld(id,label,{type='text',ph='',hint='',area=false}={}){
  const v=esc(U.co.f[id]),er=U.co.err[id];
  return `<div class="fld"><label for="f_${id}">${label}</label>${area?`<textarea id="f_${id}" data-f="${id}" rows="3" placeholder="${ph}">${v}</textarea>`:`<input id="f_${id}" data-f="${id}" type="${type}" value="${v}" placeholder="${ph}" autocomplete="off">`}${hint?`<small>${hint}</small>`:''}${er?`<span class="err" role="alert">${er}</span>`:''}</div>`;
}
function opt(name,val,checked,title,desc,amt,rr=true){return `<label class="opt"><input type="radio" name="${name}" data-f="${name}" ${rr?'data-rr':''} value="${val}" ${checked?'checked':''}><div><b>${title}</b><span>${desc}</span></div>${amt?`<span class="amt">${amt}</span>`:''}</label>`}
function vCheckout(){
  const L=lines();
  if(!L.length)return `<div class="empty"><h3>Keranjang masih kosong</h3><p>Tambahkan menu dulu sebelum checkout.</p><button class="btn pri" data-a="go" data-r="menu">Lihat menu</button></div>`;
  const f=U.co.f,st=U.co.step,t=T();let b='';
  if(st===1)b=`<h2>Periksa pesanan</h2><p class="mute" style="margin:4px 0 8px">Cek produk, jumlah, dan harga.</p>${L.map(l=>lineHTML(l)).join('')}${U.co.err.cart?`<p class="err" role="alert" style="color:var(--bad);font-weight:600;margin-top:10px">${U.co.err.cart}</p>`:''}`;
  if(st===2)b=`<h2>Data pemesan</h2><p class="mute" style="margin:4px 0 14px">Hanya data yang dibutuhkan untuk memproses pesanan.</p>${U.user?`<p class="pill ok" style="margin-bottom:12px">Masuk sebagai ${esc(U.user.email)}. Pesanan tersimpan di akunmu.</p>`:`<p class="mute" style="margin-bottom:12px">Punya akun? <button class="btn link" data-a="loginco">Masuk</button> agar data terisi otomatis dan pesanan tersimpan di akun.</p>`}${fld('nama','Nama lengkap',{ph:'Contoh: Alya Putri'})}${fld('wa','Nomor WhatsApp',{type:'tel',ph:'08123456789',hint:'Status pesanan dikirim ke nomor ini.'})}${fld('email','Email (opsional)',{type:'email',ph:'nama@email.com'})}${fld('alamat','Alamat pengantaran',{area:true,ph:'Nama jalan, nomor, patokan. Kosongkan jika ambil di outlet.',hint:'Wajib jika memilih kirim ke alamat.'})}${fld('catatan','Catatan pesanan (opsional)',{area:true,ph:'Contoh: less ice'})}`;
  if(st===3)b=`<h2>Pengiriman</h2><div class="fld" style="margin-top:12px"><span class="lb">Cara menerima pesanan</span>${opt('del','kirim',f.del==='kirim','Kirim ke alamat','Kurir instan, target tiba ≤ 60 menit.','')}${opt('del','ambil',f.del==='ambil','Ambil di outlet','Siap sekitar 15 menit setelah dibayar. Tanpa ongkir.','Gratis')}</div>
  ${f.del==='kirim'?`<div class="fld"><span class="lb">Kurir</span>${Object.entries(CFG.ship).map(([k,c])=>opt('courier',k,f.courier===k,c.name,'Estimasi '+c.eta+' (simulasi)',rp(c.fee))).join('')}<small>Alamat: ${f.alamat?esc(f.alamat):'<b>belum diisi</b>'}</small>${U.co.err.alamat?`<span class="err" role="alert">${U.co.err.alamat}</span>`:''}</div>`:`<div class="fld"><label for="f_branch">Pilih outlet</label><select id="f_branch" data-f="branch">${CFG.branches.map((x,i)=>`<option value="${i}" ${+f.branch===i?'selected':''}>${x.n}</option>`).join('')}</select></div>`}
  <div class="fld"><label for="f_promo">Kode promo (opsional)</label><div class="row"><input id="f_promo" data-f="promo" value="${esc(f.promo)}" placeholder="${CFG.promo.code}" style="flex:1;min-width:140px"><button class="btn sm" data-a="applypromo">Terapkan</button></div>${f.promoOk?`<small style="color:var(--ok);font-weight:600">Kode ${CFG.promo.code} dipakai, diskon ${CFG.promo.pct}%.</small>`:''}${U.co.err.promo?`<span class="err" role="alert">${U.co.err.promo}</span>`:''}</div>`;
  if(st===4)b=`<h2>Metode pembayaran</h2><p class="mute" style="margin:4px 0 14px">Diproses lewat Midtrans (simulasi).</p><div class="fld">${Object.entries(PAYS).map(([k,v])=>opt('pay',k,f.pay===k,v[0]+(k==='qris'?' (utama)':''),v[1],'',false)).join('')}</div>`;
  if(st===5)b=`<h2>Konfirmasi pesanan</h2><div class="panel" style="margin:12px 0"><dl class="kv"><dt>Nama</dt><dd>${esc(f.nama)}</dd><dt>WhatsApp</dt><dd>${esc(f.wa)}</dd><dt>${f.del==='kirim'?'Dikirim ke':'Ambil di'}</dt><dd>${f.del==='kirim'?esc(f.alamat):CFG.branches[f.branch].n}</dd><dt>Pengiriman</dt><dd>${f.del==='kirim'?CFG.ship[f.courier].name:'Ambil di outlet'}</dd><dt>Pembayaran</dt><dd>${PAYS[f.pay][0]}</dd>${f.catatan?`<dt>Catatan</dt><dd>${esc(f.catatan)}</dd>`:''}</dl></div>
  <div class="fld"><label class="row" style="font-weight:500"><input type="checkbox" id="f_agree" data-f="agree" ${f.agree?'checked':''} style="width:auto;accent-color:var(--leaf)"> Saya setuju dengan syarat dan ketentuan, termasuk kebijakan retur dan refund.</label>${U.co.err.agree?`<span class="err" role="alert">${U.co.err.agree}</span>`:''}</div><p class="mute" style="font-size:.9rem">Koneksi aman (HTTPS). Data hanya dipakai untuk memproses pesanan.</p>${U.co.err.server?`<p class="err" role="alert" style="color:var(--bad);font-weight:600;margin-top:10px">${esc(U.co.err.server)}</p>`:''}`;
  const nav=`<div class="row" style="justify-content:space-between;margin-top:20px">${st>1?'<button class="btn" data-a="prev">Kembali</button>':'<span></span>'}${st<5?'<button class="btn pri" data-a="next">Lanjut</button>':`<button class="btn pri" data-a="place" ${U.busy?'disabled':''}>${U.busy?'Memproses…':'Bayar '+rp(t.total)}</button>`}</div>`;
  return `<h1 style="font-size:clamp(2rem,5vw,3rem)">Checkout</h1><ol class="stepper" style="margin-top:14px">${STEPS.map((s,i)=>`<li class="${i+1===st?'on':i+1<st?'done':''}" ${i+1===st?'aria-current="step"':''}>${i+1}. ${s}</li>`).join('')}</ol>
  <div class="co"><div class="panel">${b}${nav}</div><aside class="panel sum" aria-label="Ringkasan harga"><h3>Ringkasan</h3>${L.map(l=>`<div class="row" style="justify-content:space-between;font-size:.92rem;margin-top:6px"><span>${l.qty}× ${esc(l.name)}</span><span>${rp(l.unit*l.qty)}</span></div>`).join('')}<dl><dt>Subtotal</dt><dd>${rp(t.sub)}</dd><dt>Diskon</dt><dd>${t.disc?'−'+rp(t.disc):'-'}</dd><dt>Ongkir</dt><dd>${f.del==='kirim'?rp(t.ship):'Gratis'}</dd><dt class="tot">Total</dt><dd class="tot">${rp(t.total)}</dd></dl></aside></div>`;
}
function validate(st){
  const f=U.co.f,e=U.co.err={};
  if(st===1){const bad=lines().find(l=>l.qty>avail(l.id));if(bad)e.cart=bad.name+' melebihi stok yang tersedia. Kurangi jumlahnya.'}
  if(st===2){
    if(f.nama.trim().length<2)e.nama='Isi nama lengkap.';
    if(!/^(\+?62|0)8\d{7,12}$/.test(f.wa.replace(/[\s-]/g,'')))e.wa='Nomor WhatsApp tidak valid. Contoh: 08123456789.';
    if(f.email&&!/^\S+@\S+\.\S+$/.test(f.email))e.email='Format email tidak valid.';
  }
  if(st===3&&f.del==='kirim'&&f.alamat.trim().length<10){e.alamat='Isi alamat pengantaran dengan lengkap (min. 10 karakter).';U.co.step=2;U.co.err={alamat:e.alamat}}
  if(st===5&&!f.agree)e.agree='Centang persetujuan untuk melanjutkan.';
  return !Object.keys(U.co.err).length;
}
async function placeOrder(){
  if(U.busy)return;const f=U.co.f;U.busy=true;draw();
  try{
    const d=await api('order_create.php',{items:S.cart.map(c=>({id:c.id,qty:c.qty})),cust:{nama:f.nama,wa:f.wa,email:f.email,alamat:f.alamat,catatan:f.catatan},del:f.del,courier:f.courier,branch:+f.branch,pay:f.pay,promo:f.promoOk?f.promo:''});
    const o=d.order;U.oc[o.id]=o;S.my=S.my.filter(x=>x.id!==o.id);S.my.unshift({id:o.id,wa:f.wa});save();U.cur=o.id;U.busy=false;go('bayar');
  }catch(e){
    U.busy=false;U.co.err={server:e.msg||'Pesanan gagal dibuat. Coba lagi.'};
    if(e.status===409)await refreshProducts();
    draw();
  }
}
/* ====== PEMBAYARAN ====== */
function qrSVG(seed){
  let s=0;for(const c of seed)s=(s*31+c.charCodeAt(0))>>>0;const r=()=>(s=(s*1664525+1013904223)>>>0)/4294967296;
  const n=25,cells=[];const fin=(x,y)=>(x<8&&y<8)||(x>n-9&&y<8)||(x<8&&y>n-9);
  for(let y=0;y<n;y++)for(let x=0;x<n;x++)if(!fin(x,y)&&r()>.52)cells.push(`<rect x="${x}" y="${y}" width="1" height="1"/>`);
  const f=(x,y)=>`<rect x="${x}" y="${y}" width="7" height="7"/><rect x="${x+1}" y="${y+1}" width="5" height="5" fill="#fff"/><rect x="${x+2}" y="${y+2}" width="3" height="3"/>`;
  return `<svg viewBox="-1 -1 27 27" role="img" aria-label="Kode QR simulasi" shape-rendering="crispEdges" fill="#000">${cells.join('')}${f(0,0)}${f(n-7,0)}${f(0,n-7)}</svg>`;
}
function vPay(){
  const o=U.oc[U.cur];
  if(!o)return `<div class="empty"><h3>Belum ada pembayaran</h3><p>Buat pesanan dulu dari keranjang.</p><button class="btn pri" data-a="go" data-r="menu">Lihat menu</button></div>`;
  const pn=PAYS[o.pay][0];
  let h=`<h1 style="font-size:clamp(2rem,5vw,3rem)">Pembayaran</h1><p class="mute" style="margin:6px 0 18px">Order <b>${o.id}</b>, total <b>${rp(o.total)}</b>, metode ${pn}.</p>`;
  if(o.payStatus==='berhasil')return h+`<div class="okbox"><h2>Pembayaran berhasil</h2><p style="margin:8px 0 14px">Nomor pesanan <b>${o.id}</b>. ${o.del==='kirim'?'Pesanan dibuat dan diperkirakan tiba sekitar 60 menit.':'Pesanan disiapkan, siap diambil sekitar 15 menit.'} Pemberitahuan dikirim ke WhatsApp ${esc(o.cust.wa)} (simulasi).</p><div class="row"><button class="btn pri" data-a="go" data-r="o-${o.id}">Lacak pesanan</button><button class="btn" data-a="go" data-r="menu">Kembali ke menu</button></div></div>`;
  if(o.payStatus==='gagal'||o.payStatus==='kedaluwarsa')return h+`<div class="badbox"><h2>Pembayaran ${o.payStatus==='gagal'?'gagal':'kedaluwarsa'}</h2><p style="margin:8px 0 14px">${o.reason||'Pembayaran tidak selesai.'} Pesanan belum diproses dan stok tidak berkurang. Kamu bisa bayar lagi atau memilih metode lain.</p><div class="row"><button class="btn pri" data-a="retry">Bayar lagi</button><button class="btn" data-a="cancel">Batalkan pesanan</button></div></div>`;
  if(o.payStatus==='dibatalkan')return h+`<div class="panel"><h3>Pesanan dibatalkan</h3><p class="mute" style="margin:6px 0 14px">Status: pesanan dibatalkan. Pesanan tidak diproses.</p><button class="btn pri" data-a="go" data-r="menu">Kembali ke menu</button></div>`;
  const ins={qris:`<p>Buka mobile banking atau e-wallet, pilih Scan/QRIS, lalu pindai kode ini.</p><div class="qr">${qrSVG(o.id)}</div><p class="note">Simulasi. Kode ini bukan QRIS sungguhan dan tidak bisa dibayar.</p>`,
   transfer:`<p>Transfer tepat <b>${rp(o.total)}</b> ke rekening berikut.</p><p style="margin:12px 0;font:800 1.4rem var(--display)">0000 0000 0000</p><p class="mute">a.n. Yellow Mango's (nomor contoh)</p>`,
   va:`<p>Bayar <b>${rp(o.total)}</b> ke nomor Virtual Account.</p><p style="margin:12px 0;font:800 1.4rem var(--display)">8077 ${o.id.replace(/\D/g,'').padStart(4,'0')} 0000 12</p><p class="mute">Nomor contoh. Status dicek otomatis.</p>`}[o.pay]||`<p>Kamu akan diarahkan ke aplikasi <b>${pn}</b>. Setujui pembayaran <b>${rp(o.total)}</b> di sana.</p><p class="note" style="margin-top:12px">Simulasi. Tidak ada aplikasi yang dibuka.</p>`;
  return h+`<div class="two"><div class="panel"><span class="pill warn">Menunggu pembayaran</span><p style="margin:12px 0">Selesaikan dalam <b id="cd" style="font-variant-numeric:tabular-nums">--:--</b></p>${ins}</div>
  <div class="panel"><h3>Simulasi hasil pembayaran</h3><p class="mute" style="margin:6px 0 14px">${CFG.simulate?'Di versi asli, status ini datang dari Midtrans. Di prototype, pilih sendiri hasilnya.':'Status pembayaran diperbarui otomatis setelah pembayaran diterima.'}</p>${CFG.simulate?`<div class="row"><button class="btn pri" data-a="paid" ${U.busy?'disabled':''}>Pembayaran berhasil</button><button class="btn" data-a="failed" ${U.busy?'disabled':''}>Pembayaran gagal</button></div>`:''}<div class="sp"></div><button class="btn link" data-a="cancel">Batalkan pesanan</button></div></div>`;
}
function tick(){
  const o=U.oc[U.cur];if(U.route!=='bayar'||!o||o.payStatus!=='menunggu')return;
  const ms=o.exp-nowS();
  if(ms<=0){if(!U.expiring){U.expiring=true;loadOrder(o.id).then(()=>{U.expiring=false;draw()}).catch(()=>{U.expiring=false})}return}
  const c=$('#cd');if(c)c.textContent=String(Math.floor(ms/60000)).padStart(2,'0')+':'+String(Math.floor(ms%60000/1000)).padStart(2,'0');
}
async function payAct(result){
  const o=U.oc[U.cur];if(!o||U.busy)return;U.busy=true;
  try{
    const d=await api('order_pay.php',{id:o.id,wa:waOf(o.id),result});
    U.oc[o.id]=d.order;PRODUCTS=d.products;
    if(d.order.payStatus==='berhasil'){S.cart=[];Object.assign(U.co,{step:1,err:{}});Object.assign(U.co.f,{promoOk:false,promo:'',agree:false});save();badge()}
  }catch(e){toast(e.msg||'Gagal memperbarui pembayaran');try{await loadOrder(o.id)}catch(x){}}
  U.busy=false;draw();
}
/* ====== PESANAN ====== */
function statusPill(o){
  if(o.payStatus!=='berhasil'){const m={menunggu:['warn','Menunggu pembayaran'],gagal:['bad','Pembayaran gagal'],kedaluwarsa:['bad','Kedaluwarsa'],dibatalkan:['bad','Dibatalkan']}[o.payStatus];return `<span class="pill ${m[0]}">${m[1]}</span>`}
  const s=STG[o.del];return `<span class="pill ${o.st===s.length-1?'ok':'warn'}">${s[o.st]}</span>`;
}
function allOrders(){const seen=new Set(),out=[];[...(U.user?U.ub:[]),...U.list].forEach(o=>{if(!seen.has(o.id)){seen.add(o.id);out.push(o)}});return out}
function vOrders(){
  const ps=allOrders();
  return `<div class="sec-h"><div><h1 style="font-size:clamp(2rem,5vw,3rem)">Pesanan saya</h1><p>${U.user?'Pesanan di akunmu tampil di sini, termasuk yang dibuat dari perangkat lain.':'Pesanan yang dibuat di perangkat ini. Untuk pesanan dari perangkat lain, isi nomor order dan nomor WhatsApp, atau <button class="btn link" data-a="go" data-r="masuk">masuk ke akunmu</button>.'}</p></div></div>
  <div class="panel" style="margin-bottom:20px"><div class="row"><input id="findno" placeholder="Nomor order, mis. YM-001" aria-label="Nomor order" class="ibtn" style="cursor:text"><input id="findwa" placeholder="Nomor WhatsApp" aria-label="Nomor WhatsApp" class="ibtn" style="cursor:text" inputmode="tel"><button class="btn sm" data-a="find">Lacak</button></div></div>
  ${ps.length?ps.map(o=>`<div class="panel" style="margin-bottom:12px"><div class="row" style="justify-content:space-between"><div><b>${o.id}</b><div class="mute" style="font-size:.9rem">${o.items.map(i=>i.qty+'× '+esc(i.name)).join(', ')}</div></div><div style="text-align:right"><b>${rp(o.total)}</b><div>${statusPill(o)}</div></div></div><div style="margin-top:10px"><button class="btn sm" data-a="go" data-r="o-${o.id}">Lihat detail</button></div></div>`).join(''):`<div class="empty"><h3>Belum ada pesanan</h3><p>Pesanan yang kamu buat akan muncul di sini.</p><button class="btn pri" data-a="go" data-r="menu">Pesan sekarang</button></div>`}`;
}
function vOrder(id){
  const o=U.oc[id];
  if(!o)return `<div class="empty"><h3>Pesanan tidak ditemukan</h3><p>Nomor order tidak ada di perangkat ini. Lacak dengan nomor order dan nomor WhatsApp.</p><button class="btn pri" data-a="go" data-r="pesanan">Ke Pesanan Saya</button></div>`;
  const s=STG[o.del],paid=o.payStatus==='berhasil',fin=paid&&o.st===s.length-1;
  const win=o.doneAt?o.doneAt+3600000-nowS():0;
  let ret='';
  if(fin){
    if(o.ret)ret=`<div class="panel"><h3>Pengajuan retur</h3><p style="margin:8px 0"><span class="pill ${o.ret.st==='Menunggu verifikasi'?'warn':'ok'}">${esc(o.ret.st)}</span></p><dl class="kv"><dt>Alasan</dt><dd>${esc(o.ret.reason)}</dd><dt>Solusi diminta</dt><dd>${o.ret.sol==='refund'?'Refund':'Dibuat ulang (replacement)'}</dd></dl></div>`;
    else if(win>0)ret=`<div class="panel"><h3>Ajukan retur</h3><p class="mute" style="margin:4px 0 12px">Sisa waktu pengajuan: ${Math.ceil(win/60000)} menit. Retur karena berubah pikiran tidak diterima.</p>
    <div class="fld"><label for="r_reason">Alasan</label><select id="r_reason"><option>Barang rusak atau tumpah</option><option>Salah barang atau varian</option><option>Tidak sesuai deskripsi</option></select></div>
    <div class="fld"><label for="r_sol">Solusi yang diminta</label><select id="r_sol"><option value="replacement">Dibuat ulang (replacement)</option><option value="refund">Refund</option></select></div>
    <div class="fld"><label for="r_note">Penjelasan singkat</label><textarea id="r_note" rows="2"></textarea></div>
    <div class="fld"><label for="r_file">Foto atau video bukti (wajib, maks. 8 MB)</label><input id="r_file" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm"><span class="err" id="r_err" role="alert"></span></div><button class="btn pri" data-a="return" data-id="${o.id}">Kirim pengajuan</button></div>`;
    else ret=`<div class="panel"><h3>Retur</h3><p class="mute">Batas pengajuan retur 1 jam setelah pesanan diterima sudah lewat.</p></div>`;
  }
  const tl=paid?`<ol class="tl">${s.map((x,i)=>`<li class="${i<o.st?'done':i===o.st?'now':''}">${x}${o.log.find(l=>l[1]===x)?`<small>${tm(o.log.find(l=>l[1]===x)[0])}</small>`:''}</li>`).join('')}</ol>`:`<p class="mute">Pesanan diproses setelah pembayaran berhasil.</p>${o.payStatus==='menunggu'?`<button class="btn pri" style="margin-top:10px" data-a="go" data-r="bayar" data-cur="${o.id}">Lanjutkan pembayaran</button>`:''}`;
  return `<div class="crumb"><button data-a="go" data-r="pesanan">Pesanan saya</button> / ${o.id}</div><div class="row" style="justify-content:space-between"><h1 style="font-size:clamp(2rem,5vw,3rem)">Order ${o.id}</h1>${statusPill(o)}</div>
  <p class="mute" style="margin:8px 0 20px">${o.del==='kirim'?`Estimasi tiba ±60 menit setelah pembayaran terkonfirmasi (${esc(CFG.ship[o.courier].name)}).`:`Ambil di ${esc(CFG.branches[o.branch].n)}. Tunjukkan nomor order.`}</p>
  <div class="two"><div class="panel"><h3 style="margin-bottom:12px">Status pesanan</h3>${tl}<div class="sp"></div><p class="mute" style="font-size:.88rem">Status diperbarui oleh admin lewat dashboard.</p><div class="row" style="margin-top:8px"><button class="btn sm" data-a="refresh" data-id="${o.id}">Perbarui status</button><a class="btn sm" target="_blank" rel="noopener" href="https://wa.me/${CFG.wa}?text=${encodeURIComponent('Halo, saya mau tanya pesanan '+o.id)}">Bantuan WhatsApp</a></div></div>
  <div class="panel"><h3>Rincian</h3>${o.items.map(i=>`<div class="row" style="justify-content:space-between;margin-top:6px"><span>${i.qty}× ${esc(i.name)}</span><span>${rp(i.unit*i.qty)}</span></div>`).join('')}<dl class="kv" style="margin-top:14px"><dt>Subtotal</dt><dd>${rp(o.sub)}</dd><dt>Diskon</dt><dd>${o.disc?'−'+rp(o.disc):'-'}</dd><dt>Ongkir</dt><dd>${o.ship?rp(o.ship):'Gratis'}</dd><dt>Total</dt><dd><b>${rp(o.total)}</b></dd><dt>Bayar</dt><dd>${PAYS[o.pay][0]}</dd><dt>Atas nama</dt><dd>${esc(o.cust.nama)}</dd></dl></div></div><div class="sp"></div>${ret}`;
}
/* ====== AKUN PENGGUNA (daftar, masuk, dashboard) ====== */
const PHONE_RE=/^(\+?62|0)8\d{7,12}$/;
function uf(id,label,type,key,o={}){
  const e=U.aerr[key],v=U.af[key]||'';
  const attrs=`id="${id}" data-u="${key}" ${o.ac?`autocomplete="${o.ac}"`:''} ${o.mode?`inputmode="${o.mode}"`:''} ${o.ro?'readonly':''} ${e?`aria-invalid="true" aria-describedby="${id}_e"`:''}`;
  const input=o.area?`<textarea ${attrs} rows="3" maxlength="300">${esc(v)}</textarea>`:`<input ${attrs} type="${type}" value="${esc(v)}">`;
  return `<div class="fld"><label for="${id}">${label}</label>${input}${o.hint&&!e?`<small>${o.hint}</small>`:''}${e?`<span class="err" id="${id}_e" role="alert">${esc(e)}</span>`:''}</div>`;
}
function srvErr(){return U.aerr.server?`<p role="alert" style="color:var(--bad);font-weight:600;margin:12px 0">${esc(U.aerr.server)}</p>`:''}
function vAuth(reg){
  if(U.user)return `<div class="empty"><h3>Kamu sudah masuk</h3><p>Masuk sebagai ${esc(U.user.email)}.</p><button class="btn pri" data-a="go" data-r="akun">Buka dashboard</button></div>`;
  return `<div class="sec-h"><div><h1 style="font-size:clamp(2rem,5vw,3rem)">${reg?'Daftar akun':'Masuk'}</h1><p>${reg?'Buat akun agar data pengiriman terisi otomatis dan riwayat pesanan tersimpan.':'Masuk untuk membuka dashboard dan riwayat pesananmu.'}</p></div></div>
  <form id="authf" class="panel" style="max-width:460px" novalidate>
    ${reg?uf('u_nama','Nama lengkap','text','nama',{ac:'name'}):''}
    ${uf('u_email','Email','email','email',{ac:'email',mode:'email'})}
    ${reg?uf('u_hp','Nomor WhatsApp','tel','hp',{ac:'tel',mode:'tel',hint:'Contoh: 08123456789'}):''}
    ${uf('u_pw','Password','password','password',{ac:reg?'new-password':'current-password',hint:reg?'Minimal 8 karakter, berisi huruf dan angka.':''})}
    ${reg?uf('u_pw2','Ulangi password','password','password2',{ac:'new-password'}):''}
    <label class="chk"><input type="checkbox" id="showpw"> Tampilkan password</label>
    ${srvErr()}
    <button class="btn pri" type="submit" style="margin-top:14px" ${U.busy?'disabled':''}>${U.busy?'Memproses…':reg?'Daftar':'Masuk'}</button>
    <p class="mute" style="margin-top:14px;font-size:.92rem">${reg?'Sudah punya akun? <button type="button" class="btn link" data-a="go" data-r="masuk">Masuk</button>':'Belum punya akun? <button type="button" class="btn link" data-a="go" data-r="daftar">Daftar gratis</button>'}</p>
  </form>`;
}
function focusErr(){setTimeout(()=>{const e=document.querySelector('[aria-invalid=true]');if(e)e.focus()},0)}
function pwCheck(p,key,e){
  if(!p)e[key]='Isi password.';
  else if(p.length<8)e[key]='Password minimal 8 karakter.';
  else if(p.length>72)e[key]='Password maksimal 72 karakter.';
  else if(!/[A-Za-z]/.test(p)||!/\d/.test(p))e[key]='Password harus berisi huruf dan angka.';
}
async function doAuth(reg){
  if(U.busy)return;const f=U.af,e={},em=(f.email||'').trim();
  if(reg&&(f.nama||'').trim().length<2)e.nama='Isi nama lengkap.';
  if(!/^\S+@\S+\.\S+$/.test(em))e.email='Format email tidak valid.';
  if(reg&&!PHONE_RE.test((f.hp||'').replace(/[\s-]/g,'')))e.hp='Nomor WhatsApp tidak valid. Contoh: 08123456789.';
  if(reg)pwCheck(f.password||'','password',e);else if(!f.password)e.password='Isi password.';
  if(reg&&!e.password&&f.password!==f.password2)e.password2='Konfirmasi password tidak sama.';
  U.aerr=e;
  if(Object.keys(e).length){draw();focusErr();return}
  U.busy=true;draw();
  try{
    const d=await api(reg?'user_register.php':'user_login.php',reg?{nama:f.nama,email:em,hp:f.hp,password:f.password,password2:f.password2}:{email:em,password:f.password});
    setUser(d);U.af={};U.busy=false;toast(reg?'Akun berhasil dibuat':'Berhasil masuk');
    const n=U.next||'akun';U.next=null;go(n);
  }catch(x){U.busy=false;U.aerr=x.field?{[x.field]:x.msg}:{server:x.msg||'Gagal memproses.'};draw();focusErr()}
}
async function doLogout(){
  try{await api('user_logout.php',{})}catch(e){}
  U.user=null;U.ub=[];U.af={};Object.assign(U.co.f,{nama:'',wa:'',email:'',alamat:''});
  toast('Kamu sudah keluar');go('beranda');
}
async function saveProfil(){
  if(U.busy)return;const f=U.af,e={};
  if((f.nama||'').trim().length<2)e.nama='Isi nama lengkap.';
  if(!PHONE_RE.test((f.hp||'').replace(/[\s-]/g,'')))e.hp='Nomor WhatsApp tidak valid. Contoh: 08123456789.';
  U.aerr=e;if(Object.keys(e).length){draw();focusErr();return}
  U.busy=true;draw();
  try{const d=await api('user_update.php',{action:'profil',nama:f.nama,hp:f.hp,alamat:f.alamat||''});setUser(d);Object.assign(U.co.f,{nama:d.user.nama,wa:d.user.hp,alamat:d.user.alamat});U.af={nama:d.user.nama,hp:d.user.hp,alamat:d.user.alamat||''};toast('Profil disimpan')}
  catch(x){U.aerr=x.field?{[x.field]:x.msg}:{server:x.msg||'Gagal menyimpan.'}}
  U.busy=false;draw();if(Object.keys(U.aerr).length)focusErr();
}
async function savePass(){
  if(U.busy)return;const f=U.af,e={};
  if(!f.lama)e.lama='Isi password lama.';
  pwCheck(f.baru||'','baru',e);
  if(!e.baru&&f.baru!==f.baru2)e.baru2='Konfirmasi password baru tidak sama.';
  U.aerr=e;if(Object.keys(e).length){draw();focusErr();return}
  U.busy=true;draw();
  try{const d=await api('user_update.php',{action:'sandi',lama:f.lama,baru:f.baru,baru2:f.baru2});setUser(d);U.af={};toast('Password berhasil diubah')}
  catch(x){U.aerr=x.field?{[x.field]:x.msg}:{server:x.msg||'Gagal mengubah password.'}}
  U.busy=false;draw();if(Object.keys(U.aerr).length)focusErr();
}
function vAccount(){
  const u=U.user,os=U.ub,paid=os.filter(o=>o.payStatus==='berhasil'),spent=paid.reduce((a,o)=>a+o.total,0);
  const active=paid.filter(o=>o.st<STG[o.del].length-1).length;
  const since=new Date(u.dibuat).toLocaleDateString('id-ID',{day:'numeric',month:'long',year:'numeric'});
  let body='';
  if(U.atab==='pesanan'){
    body=os.length?os.map(o=>`<div class="panel" style="margin-bottom:12px"><div class="row" style="justify-content:space-between"><div><b>${o.id}</b><div class="mute" style="font-size:.9rem">${o.items.map(i=>i.qty+'× '+esc(i.name)).join(', ')}</div></div><div style="text-align:right"><b>${rp(o.total)}</b><div>${statusPill(o)}</div></div></div><div style="margin-top:10px"><button class="btn sm" data-a="go" data-r="o-${o.id}">Lihat detail</button>${o.payStatus==='menunggu'?`<button class="btn sm pri" data-a="go" data-r="bayar" data-cur="${o.id}">Bayar</button>`:''}</div></div>`).join(''):`<div class="empty"><h3>Belum ada pesanan</h3><p>Pesanan yang kamu buat saat masuk akan tersimpan di sini.</p><button class="btn pri" data-a="go" data-r="menu">Pesan sekarang</button></div>`;
  }else if(U.atab==='profil'){
    body=`<form id="profilf" class="panel" style="max-width:520px" novalidate><h3 style="margin-bottom:12px">Data diri</h3>
    ${uf('p_nama','Nama lengkap','text','nama',{ac:'name'})}
    <div class="fld"><label for="p_email">Email</label><input id="p_email" type="email" value="${esc(u.email)}" readonly><small>Email dipakai untuk masuk dan tidak bisa diubah.</small></div>
    ${uf('p_hp','Nomor WhatsApp','tel','hp',{ac:'tel',mode:'tel'})}
    ${uf('p_alamat','Alamat pengantaran utama','text','alamat',{area:true,hint:'Terisi otomatis saat checkout.'})}
    ${srvErr()}<button class="btn pri" type="submit" ${U.busy?'disabled':''}>${U.busy?'Menyimpan…':'Simpan perubahan'}</button></form>`;
  }else{
    body=`<form id="sandif" class="panel" style="max-width:520px" novalidate><h3 style="margin-bottom:12px">Ubah password</h3>
    ${uf('s_lama','Password lama','password','lama',{ac:'current-password'})}
    ${uf('s_baru','Password baru','password','baru',{ac:'new-password',hint:'Minimal 8 karakter, berisi huruf dan angka.'})}
    ${uf('s_baru2','Ulangi password baru','password','baru2',{ac:'new-password'})}
    ${srvErr()}<button class="btn pri" type="submit" ${U.busy?'disabled':''}>${U.busy?'Menyimpan…':'Ubah password'}</button></form>`;
  }
  return `<div class="uhead"><div><h1 style="font-size:clamp(2rem,5vw,3rem)">Halo, ${esc(u.nama.split(' ')[0])}</h1><p class="mute">${esc(u.email)} · anggota sejak ${since}</p></div><div class="row"><button class="btn pri sm" data-a="go" data-r="menu">Pesan lagi</button><button class="btn sm" data-a="ulogout">Keluar</button></div></div>
  <div class="sp"></div>
  <div class="stats"><div><span>Total pesanan</span><b>${os.length}</b></div><div><span>Sedang diproses</span><b>${active}</b></div><div><span>Total belanja</span><b>${rp(spent)}</b></div></div>
  <div class="tabs" role="group" aria-label="Bagian akun">${[['pesanan','Pesanan saya'],['profil','Profil'],['sandi','Ubah password']].map(t=>`<button data-a="utab" data-k="${t[0]}" aria-pressed="${U.atab===t[0]}">${t[1]}</button>`).join('')}</div>${body}`;
}
/* ====== ADMIN (LK 6: dashboard order + stok sederhana / OMS ringan) ====== */
function vAdmin(){
  const A=U.admin;
  if(!A)return `<div class="sec-h"><div><h1 style="font-size:clamp(2rem,5vw,3rem)">Admin</h1><p>Masuk untuk mengelola pesanan, stok, dan harga.</p></div></div>
  <form id="loginf" class="panel" style="max-width:420px" novalidate><div class="fld"><label for="a_user">Username</label><input id="a_user" autocomplete="username" value="${esc(U.lastUser)}"></div><div class="fld"><label for="a_pass">Password</label><input id="a_pass" type="password" autocomplete="current-password"></div>${U.adminErr?`<p role="alert" style="color:var(--bad);font-weight:600;margin-bottom:12px">${esc(U.adminErr)}</p>`:''}<button class="btn pri" type="submit" ${U.busy?'disabled':''}>${U.busy?'Memeriksa…':'Masuk'}</button><p class="mute" style="margin-top:14px;font-size:.9rem">Akun awal untuk demo: admin / admin123. Ganti sebelum website dipakai online (lihat README).</p></form>`;
  const orders=A.orders,prods=A.products.filter(p=>!p.soon);
  const paid=orders.filter(o=>o.payStatus==='berhasil'),rev=paid.reduce((a,o)=>a+o.total,0),fee=rev*CFG.mdr;
  const pst=p=>p.stock<=0?'<span class="pill bad">Out of Stock</span>':p.stock<=CFG.minStock?'<span class="pill warn">Restock</span>':'<span class="pill ok">Ready</span>';
  const fc={mangga:[10,15],alpukat:[8,12],salad:[6,9]};
  return `<div class="sec-h"><div><h1 style="font-size:clamp(2rem,5vw,3rem)">Dashboard admin</h1><p>Masuk sebagai <b>${esc(A.name)}</b>. Data langsung dari database MySQL.</p></div><div class="row"><button class="btn sm" data-a="adminrefresh">Muat ulang</button><button class="btn sm" data-a="logout">Keluar</button></div></div>
  <div class="stats"><div><span>Total pesanan</span><b>${orders.length}</b></div><div><span>Sudah dibayar</span><b>${paid.length}</b></div><div><span>Penjualan</span><b>${rp(rev)}</b></div><div><span>Biaya MDR ${(CFG.mdr*100).toFixed(1).replace('.',',')}%</span><b>${rp(fee)}</b></div><div><span>Dana bersih</span><b>${rp(rev-fee)}</b></div></div>
  <h2 style="font-size:1.4rem;margin-bottom:12px">Produk, harga, dan stok</h2>
  <div class="tw"><table><thead><tr><th>Produk</th><th>Harga</th><th>Harga promo</th><th>Stok</th><th>Level</th><th>Status</th><th>Aksi</th></tr></thead><tbody>${prods.map(p=>`<tr><td><b>${esc(p.short)}</b><div class="mute" style="font-size:.82rem">Restock ≤ ${CFG.minStock} → ${p.cap}</div></td><td><input class="ain" id="ap_h_${p.id}" type="number" min="1000" step="500" value="${p.price}" aria-label="Harga ${esc(p.short)}"></td><td><input class="ain" id="ap_p_${p.id}" type="number" min="0" step="500" value="${p.promo??''}" placeholder="kosong" aria-label="Harga promo ${esc(p.short)}"></td><td><input class="ain" id="ap_s_${p.id}" type="number" min="0" value="${p.stock}" aria-label="Stok ${esc(p.short)}"> / ${p.cap}</td><td><div class="bar-s ${p.stock<=CFG.minStock?'low':''}"><i style="width:${p.cap?Math.min(100,p.stock/p.cap*100):0}%"></i></div></td><td>${pst(p)}</td><td><div class="row" style="margin:0"><button class="btn sm pri" data-a="saveprod" data-id="${p.id}">Simpan</button><button class="btn sm" data-a="restock" data-id="${p.id}">Restock</button></div></td></tr>`).join('')}</tbody></table></div>
  <h2 style="font-size:1.4rem;margin:32px 0 12px">Pesanan</h2>
  ${orders.length?`<div class="tw"><table><thead><tr><th>No.</th><th>Pelanggan</th><th>Item</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead><tbody>${orders.map(o=>{const s=STG[o.del],can=o.payStatus==='berhasil'&&o.st<s.length-1;return `<tr><td><b>${o.id}</b></td><td>${esc(o.cust.nama)}<div class="mute" style="font-size:.85rem">${esc(o.cust.wa)} · ${o.del==='kirim'?'Kirim':'Pickup'}</div>${o.del==='kirim'?`<div class="mute" style="font-size:.82rem">${esc(o.cust.alamat)}</div>`:''}</td><td>${o.items.map(i=>i.qty+'× '+esc(i.name)).join(', ')}</td><td>${rp(o.total)}</td><td>${statusPill(o)}${o.ret?`<div class="mute" style="font-size:.85rem">Retur: ${esc(o.ret.st)} · <a href="uploads/${esc(o.ret.file)}" target="_blank" rel="noopener">bukti</a></div>`:''}</td><td>${can?`<button class="btn sm" data-a="advance" data-id="${o.id}">${esc(s[o.st+1])}</button>`:''}${o.ret&&o.ret.st==='Menunggu verifikasi'?` <button class="btn sm pri" data-a="approve" data-id="${o.id}">Setujui retur</button>`:''}</td></tr>`}).join('')}</tbody></table></div>`:`<div class="empty"><h3>Belum ada pesanan</h3><p>Buat pesanan dari halaman Menu, lalu lihat di sini.</p></div>`}
  <h2 style="font-size:1.4rem;margin:32px 0 6px">Perencanaan stok (LK 6)</h2><p class="mute" style="margin-bottom:12px">Safety stock ${CFG.minStock} cup. Prediksi promo = penjualan normal + 50%.</p>
  ${tbl(['Produk','Normal/hari','Saat promo/hari','Safety stock','Reorder trigger'],Object.entries(fc).map(([id,v])=>[P(id).short,'±'+v[0]+' cup','±'+v[1]+' cup',CFG.minStock+' cup','Stok ≤ '+CFG.minStock+' → restock sampai '+P(id).cap]))}
  <div class="panel" style="margin-top:24px"><h3>Level supply chain digital</h3><p class="mute" style="margin-top:6px">Saat ini Level 1 (Manual) menuju Level 2 (Analytical): rekap penjualan mingguan untuk menentukan safety stock dan reorder.</p></div>`;
}
async function adminCall(data,okMsg){
  if(U.busy)return;U.busy=true;
  try{const d=await api('admin_action.php',data);PRODUCTS=d.products;U.admin={name:U.admin.name,products:d.products,orders:d.orders};if(okMsg)toast(okMsg)}
  catch(e){if(e.status===401){U.admin=false;U.adminErr='Sesi habis. Masuk lagi.'}else toast(e.msg||'Gagal menyimpan')}
  U.busy=false;draw();
}
async function doLogin(){
  if(U.busy)return;const u=$('#a_user').value,p=$('#a_pass').value;U.lastUser=u;U.adminErr='';U.busy=true;draw();
  try{const d=await api('admin_login.php',{username:u,password:p});U.admin={name:d.name,products:d.products,orders:d.orders}}catch(e){U.adminErr=e.msg||'Gagal masuk'}
  U.busy=false;draw();
}
/* ====== BLUEPRINT (ringkasan LK 2 - 6) ====== */
const BP={
'2':{t:'LK 2: Model bisnis',b:()=>`<div class="two"><div>${tbl(['Komponen','Keputusan'],[['Bisnis','Yellow Mango\'s (F&B), kelompok Oopsie'],['Model','D2C: jual langsung ke konsumen lewat website'],['Masalah','Info menu, lokasi, jam buka, dan cara pesan tersebar; pelanggan harus DM'],['Solusi','Satu pusat informasi dan pemesanan'],['Target','Pelajar, mahasiswa, pekerja kantoran, 15–35 tahun, Makassar'],['Persona','Alya, 21 tahun, mahasiswa, butuh minuman segar'],['Revenue','Penjualan langsung produk minuman buah dan dessert']])}</div><div>${tbl(['Komponen','Keputusan'],[['Kompetitor','jff_juiceforfun, King Mango Thai, Juice Code'],['Pembeda','Buah segar, beberapa cabang, website untuk info dan transaksi'],['Pemasaran','Instagram, TikTok, Facebook, WhatsApp, website'],['Ketua / Business Manager','Nur Asizah S.'],['Marketing','Hilda Selfianti'],['Product, Platform & Operations','Nadien Bija Manurun'],['Fitur pembeda','Rekomendasi promo cerdas (rencana). Di prototype diwakili kode promo dan paket bundling.']])}</div></div><h3 style="margin:20px 0 6px">Alur transaksi</h3>${chain(['Buka website','Lihat katalog','Pilih produk','Keranjang','Checkout','Bayar','Pesanan diproses','Pesanan diterima'])}`},
'3':{t:'LK 3: Teknologi platform',b:()=>`<div class="two"><div>${tbl(['Komponen','Keputusan'],[['Platform','Coding Web'],['Frontend','HTML, CSS, JavaScript'],['Backend','PHP (rencana)'],['Database','MySQL (rencana)'],['Payment','Payment gateway (Midtrans)'],['Logistik','Kurir / pickup'],['Integrasi','Payment gateway, WhatsApp, analytics, POS'],['Fitur prioritas','Beranda, katalog, detail produk, keranjang, checkout, status pesanan, info cabang, promo']])}<p class="note" style="margin-top:12px">Website ini mengikuti LK 3: tampilan HTML, CSS, JavaScript; proses di server dengan PHP (folder api/); data produk, pelanggan, dan pesanan di MySQL (database db_yellow_mangos). Pembayaran masih simulasi sampai Midtrans dihubungkan.</p></div><div>${tbl(['Tahap','PIC','Target'],[['Planning','Semua anggota','Minggu 1'],['Platform setup','Asizah','Minggu 1'],['Content & product','Hilda','Minggu 2'],['Design / prototype','Nadien','Minggu 2'],['Integrasi / simulasi','Semua anggota','Minggu 3'],['Testing & finalisasi','Semua anggota','Minggu 3']])}<h3 style="margin:18px 0 8px">Risiko dan mitigasi</h3>${tbl(['Risiko','Mitigasi'],[['Kesulitan teknis','Bagi tugas, testing bertahap'],['Keterlambatan','Timeline dan cek progres'],['Fitur terlalu banyak','Fitur utama dulu'],['Integrasi belum tersedia','Pakai simulasi pembayaran dan pengiriman']])}</div></div>`},
'4':{t:'LK 4: Pembayaran dan checkout',b:()=>`<div class="two"><div>${tbl(['Komponen','Keputusan'],[['Metode utama','QRIS'],['Alternatif','E-wallet (GoPay, ShopeePay, DANA), transfer bank, virtual account'],['Payment gateway','Midtrans'],['Cakupan','Domestik, Rupiah, tanpa multi-currency'],['Model biaya','MDR (persentase)'],['Urutan checkout','Cart → Data → Pengiriman/Pickup → Pembayaran → Konfirmasi'],['Status','Menunggu, Berhasil, Gagal']])}<h3 style="margin:18px 0 8px">Simulasi biaya, transaksi Rp35.000</h3>${tbl(['Skema','Biaya','Dana bersih'],[['MDR 0,7%','Rp245','Rp34.755'],['Flat Rp2.500','Rp2.500','Rp32.500']])}<p class="mute" style="margin-top:8px">Dipilih MDR karena biaya flat terasa besar untuk harga produk rendah.</p></div><div><h3 style="margin-bottom:6px">Alur pembayaran</h3>${chain(['Customer / checkout','Pilih metode','Midtrans / bank / e-wallet','Konfirmasi','Order berhasil','Dana diterima'])}<h3 style="margin:18px 0 8px">Skenario status</h3>${tbl(['Skenario','Yang dilihat customer'],[['Berhasil','Tulisan berhasil dan nomor pesanan'],['Gagal','Tulisan gagal dan pilihan bayar kembali'],['Belum selesai','Status menunggu pembayaran'],['Dibatalkan','Status pesanan dibatalkan']])}</div></div>`},
'5':{t:'LK 5: Katalog produk',b:()=>`<p class="note" style="margin-bottom:14px">LK 5 mencatat dua set harga: Rp13.000–15.000 di Bagian 2 dan Rp18.000–20.000 di wireframe dan bundling. Website memakai set kedua karena cocok dengan hitungan bundling Rp18.000 + Rp20.000 = Rp38.000. Harga promo Smoothies Mangga (Rp16.000) mengikuti selisih diskon Rp2.000 dari LK 5. Ubah lewat dashboard admin atau tabel produk di phpMyAdmin.</p><div class="two"><div>${tbl(['Produk','Kategori','Harga','Stok awal','Peran'],[['Smoothies Mangga','Smoothies','Rp18.000','20 cup','Hero'],['Smoothies Alpukat','Smoothies','Rp18.000','20 cup','Utama'],['Salad Buah','Dessert','Rp20.000','15 cup','Utama'],['Taiwan Dessert','Dessert','Belum ada','Belum ada','Segera hadir']])}<h3 style="margin:18px 0 8px">Bundling</h3>${tbl(['Paket','Isi','Harga'],[['Mango Fresh Combo','1 Smoothies Mangga + 1 Salad Buah','Rp35.000 (normal Rp38.000)']])}</div><div>${tbl(['Produk','Keyword utama','Long-tail'],[['Smoothies Mangga','smoothies mangga','smoothies mangga segar'],['Smoothies Alpukat','smoothies alpukat','smoothies alpukat segar'],['Salad Buah','salad buah','salad buah segar']])}<h3 style="margin:18px 0 8px">Struktur dan SEO</h3><ul style="margin:0;padding-left:20px"><li>Hierarki: Menu → Smoothies / Dessert</li><li>Filter kategori dan harga; sorting terlaris, terbaru, harga terendah</li><li>Tag: Best Seller, Promo, New</li><li>Alt text gambar: "Smoothies Mangga Yellow Mango's"</li><li>Off-page: Instagram dan TikTok</li><li>Product schema (JSON-LD) dipasang otomatis di halaman detail</li></ul></div></div>`},
'6':{t:'LK 6: Logistik dan supply chain',b:()=>`<blockquote style="margin-bottom:20px">Yellow Mango's berjanji mengantar smoothies dan salad buah tetap segar, utuh, dan tidak tumpah ke alamat customer di Makassar paling lama 60 menit setelah pembayaran terkonfirmasi.</blockquote><div class="two"><div>${tbl(['Tahap','PIC','Target'],[['Order diterima','Admin (otomatis)','≤ 2 menit'],['Picking','Tim dapur','≤ 5 menit'],['Packing','Tim dapur','≤ 10 menit'],['Handover','Admin dan kurir','≤ 10 menit'],['Delivery','Kurir','≤ 30 menit'],['Retur','Admin / CS','Respon ≤ 1 jam']])}${chain(['Supplier','Dapur / produksi','Penyimpanan dingin','Packing','GoSend / GrabExpress / Pickup','Customer'])}</div><div>${tbl(['Keputusan','Pilihan'],[['Model fulfillment','Hybrid (in-house + kurir instan)'],['Kurir','GoSend (utama), GrabExpress (cadangan), pickup outlet'],['Packing','Cup tertutup + segel, cup holder, cooler bag + es'],['Sumber stok','Satu sumber: stok dapur (spreadsheet / database website)'],['Retur','Maks. 1 jam, bukti foto/video, refund atau dibuat ulang'],['Level digital','Level 1 Manual, target Level 2 Analytical (12 bulan)'],['Teknologi prioritas','Dashboard order dan stok, nomor order/QR di label, rekap penjualan']])}</div></div>`}
};
function vBlueprint(){
  const b=BP[U.tab];
  return `<div class="sec-h"><div><h1 style="font-size:clamp(2rem,5vw,3rem)">Blueprint project</h1><p>Ringkasan keputusan dari lima lembar kerja yang menjadi dasar website ini. Kelompok Oopsie, E-Commerce, kelas C 24.</p></div></div>
  <div class="tabs" role="group" aria-label="Pilih lembar kerja">${Object.entries(BP).map(([k,v])=>`<button data-a="tab" data-k="${k}" aria-pressed="${U.tab===k}">${v.t}</button>`).join('')}</div>${b.b()}`;
}
/* ====== ROUTER & EVENT ====== */
function errBox(m){return `<div class="badbox"><h2>Website belum bisa terhubung ke database</h2><p style="margin:8px 0 14px">${esc(m)}</p><button class="btn" data-a="reload">Coba lagi</button></div>`}
function view(){
  const r=U.route;
  if(r==='beranda')return vHome();
  if(r==='menu')return vMenu();
  if(r.startsWith('p-'))return vProduct(r.slice(2));
  if(r==='promo')return vPromo();
  if(r==='cabang')return vCabang();
  if(r==='checkout')return vCheckout();
  if(r==='bayar')return vPay();
  if(r==='pesanan')return vOrders();
  if(r.startsWith('o-'))return vOrder(r.slice(2));
  if(r==='masuk')return vAuth(false);
  if(r==='daftar')return vAuth(true);
  if(r==='akun')return U.user?vAccount():vAuth(false);
  if(r==='admin')return vAdmin();
  if(r==='blueprint')return vBlueprint();
  return vHome();
}
function meta(){
  const r=U.route;let t="Yellow Mango's",d=$('meta[name=description]');
  const old=document.getElementById('ld');if(old)old.remove();
  if(r.startsWith('p-')&&P(r.slice(2))){
    const p=P(r.slice(2));t=p.name;
    if(!p.soon){const s=document.createElement('script');s.type='application/ld+json';s.id='ld';s.textContent=JSON.stringify({'@context':'https://schema.org','@type':'Product',name:p.name,description:p.brief,brand:{'@type':'Brand',name:"Yellow Mango's"},offers:{'@type':'Offer',priceCurrency:'IDR',price:eff(p),availability:avail(p.id)>0?'https://schema.org/InStock':'https://schema.org/OutOfStock'},aggregateRating:{'@type':'AggregateRating',ratingValue:p.rating,reviewCount:p.rev}});document.head.appendChild(s);}
    if(d)d.content=p.brief;
  }else{const n=NAV.find(x=>x[0]===r),at={masuk:'Masuk',daftar:'Daftar',akun:'Akun saya'}[r];if(n)t=n[1]+" | Yellow Mango's";else if(at)t=at+" | Yellow Mango's"}
  document.title=t;
}
async function prep(){
  const r=U.route;
  if(r==='bayar'){U.cur=U.cur||(S.my[0]&&S.my[0].id);if(U.cur)await loadOrder(U.cur);return}
  if(r.startsWith('o-')){await loadOrder(r.slice(2));return}
  if(r==='pesanan'){if(U.user)await refreshUser();await loadList();return}
  if(r==='akun'){if(U.user)await refreshUser();return}
  if(r==='checkout')prefill();
  if(r==='admin'){try{U.admin=await api('admin_data.php')}catch(e){if(e.status===401)U.admin=false;else throw e}return}
  if(['beranda','menu','promo','checkout'].includes(r)||r.startsWith('p-'))await refreshProducts();
}
function draw(){
  $('#app').innerHTML=U.perr?errBox(U.perr):view();
  const g=U.route.startsWith('p-')?'menu':U.route.startsWith('o-')?'pesanan':U.route;
  $('#nav').innerHTML=NAV.map(n=>`<button data-a="go" data-r="${n[0]}" ${g===n[0]?'aria-current="page"':''}>${n[1]}</button>`).join('');
  const ub=$('#userBtn');if(ub){ub.textContent=U.user?U.user.nama.split(' ')[0].slice(0,12):'Masuk';ub.dataset.r=U.user?'akun':'masuk';ub.setAttribute('aria-label',U.user?'Akun saya':'Masuk atau daftar');if(['akun','masuk','daftar'].includes(U.route))ub.setAttribute('aria-current','page');else ub.removeAttribute('aria-current')}
  badge();if(!U.perr)meta();tick();
}
let rseq=0;
async function render(){
  const my=++rseq;U.perr='';
  try{await prep()}catch(e){U.perr=e.msg||'Gagal memuat data.'}
  if(my!==rseq)return;
  draw();
}
function go(r,cur){
  closeLayer();U.aerr={};U.route=r;if(cur)U.cur=cur;if(r.startsWith('p-')){U.pq=1;U.view=0}
  try{history.pushState(null,'','#'+r)}catch(e){}
  return render().then(()=>window.scrollTo(0,0));
}
window.addEventListener('popstate',()=>{U.route=(location.hash||'#beranda').slice(1)||'beranda';render()});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeLayer()});
document.addEventListener('submit',e=>{const id=e.target.id;if(id==='loginf'){e.preventDefault();doLogin()}if(id==='authf'){e.preventDefault();doAuth(U.route==='daftar')}if(id==='profilf'){e.preventDefault();saveProfil()}if(id==='sandif'){e.preventDefault();savePass()}});
document.addEventListener('input',e=>{
  const t=e.target;
  if(t.id==='q'){U.q=t.value;$('#grid').innerHTML=gridHTML();return}
  if(t.dataset.u){U.af[t.dataset.u]=t.value;return}
  if(t.dataset.f){U.co.f[t.dataset.f]=t.type==='checkbox'?t.checked:(t.dataset.f==='branch'?+t.value:t.value)}
});
document.addEventListener('change',e=>{
  const t=e.target;
  if(t.id==='showpw'){document.querySelectorAll('#authf input[data-u^=password]').forEach(i=>i.type=t.checked?'text':'password');return}
  if(t.id==='fcat'||t.id==='fprice'||t.id==='fsort'){U[{fcat:'cat',fprice:'price',fsort:'sort'}[t.id]]=t.value;$('#grid').innerHTML=gridHTML();return}
  if(t.dataset.f){U.co.f[t.dataset.f]=t.type==='checkbox'?t.checked:(t.dataset.f==='branch'?+t.value:t.value);if(t.dataset.rr!==undefined)draw()}
});
document.addEventListener('click',e=>{
  const b=e.target.closest('[data-a]');if(!b)return;
  const d=b.dataset,a=d.a;
  switch(a){
    case 'go':go(d.r,d.cur);break;
    case 'reload':render();break;
    case 'add':if(addCart(d.id))if($('.drawer'))drawer();break;
    case 'addq':addCart(d.id,U.pq||1);break;
    case 'buy':if(addCart(d.id,U.pq||1)){U.co.step=1;go('checkout')}break;
    case 'pq':{const m=Math.max(1,Math.min(Math.max(1,avail(U.route.slice(2))),(U.pq||1)+ +d.d));U.pq=m;$('#pq').textContent=m;break}
    case 'view':U.view=+d.i;draw();break;
    case 'qty':{const c=S.cart.find(x=>x.id===d.id);if(!c)break;const n=c.qty+ +d.d;if(n<1){S.cart=S.cart.filter(x=>x!==c)}else if(n>avail(d.id)){toast('Stok hanya '+avail(d.id))}else c.qty=n;save();badge();$('.drawer')?drawer():draw();break}
    case 'rm':S.cart=S.cart.filter(x=>x.id!==d.id);save();badge();$('.drawer')?drawer():draw();break;
    case 'cart':drawer();break;
    case 'close':closeLayer();break;
    case 'checkout':U.co.step=1;U.co.err={};go('checkout');break;
    case 'theme':{const r=document.documentElement,dark=r.dataset.theme?r.dataset.theme==='dark':matchMedia('(prefers-color-scheme:dark)').matches;r.dataset.theme=dark?'light':'dark';break}
    case 'reset':U.q='';U.cat='all';U.price='all';U.sort='terlaris';draw();break;
    case 'copy':try{navigator.clipboard.writeText(d.t).then(()=>toast('Kode disalin'),()=>toast('Salin manual: '+d.t))}catch(x){toast('Salin manual: '+d.t)}break;
    case 'prev':U.co.err={};U.co.step=Math.max(1,U.co.step-1);draw();window.scrollTo(0,0);break;
    case 'next':if(validate(U.co.step))U.co.step++;draw();window.scrollTo(0,0);break;
    case 'applypromo':{U.co.err={};if(U.co.f.promo.trim().toUpperCase()===CFG.promo.code){U.co.f.promoOk=true;toast('Kode promo dipakai')}else{U.co.f.promoOk=false;U.co.err.promo='Kode promo tidak dikenali.'}draw();break}
    case 'place':if(validate(5)&&validate(1))placeOrder();else draw();break;
    case 'paid':payAct('berhasil');break;
    case 'failed':payAct('gagal');break;
    case 'retry':payAct('ulang');break;
    case 'cancel':payAct('batal');break;
    case 'refresh':loadOrder(d.id).then(()=>{draw();toast('Status diperbarui')}).catch(x=>toast(x.msg));break;
    case 'find':{const id=($('#findno').value||'').trim().toUpperCase(),wa=($('#findwa').value||'').trim();if(!id||!wa){toast('Isi nomor order dan nomor WhatsApp');break}
      api('order_get.php',{id,wa}).then(dd=>{U.oc[id]=dd.order;if(!myRef(id)){S.my.unshift({id,wa});save()}go('o-'+id)}).catch(x=>toast(x.msg||'Pesanan tidak ditemukan'));break}
    case 'return':{
      const o=U.oc[d.id],f=$('#r_file');if(!o)break;
      if(!f.files.length){$('#r_err').textContent='Unggah foto atau video bukti.';break}
      const fd=new FormData();fd.append('id',o.id);fd.append('wa',waOf(o.id));fd.append('reason',$('#r_reason').value);fd.append('sol',$('#r_sol').value);fd.append('note',$('#r_note').value);fd.append('file',f.files[0]);
      api('order_return.php',fd,true).then(dd=>{U.oc[o.id]=dd.order;toast('Pengajuan retur dikirim');draw()}).catch(x=>{$('#r_err').textContent=x.msg||'Gagal mengirim'});break}
    case 'advance':adminCall({action:'advance',id:d.id},'Status diperbarui');break;
    case 'approve':adminCall({action:'approve',id:d.id},'Retur disetujui');break;
    case 'restock':adminCall({action:'restock',id:d.id},'Stok diisi penuh');break;
    case 'saveprod':adminCall({action:'updateproduct',id:d.id,price:$('#ap_h_'+d.id).value,promo:$('#ap_p_'+d.id).value,stock:$('#ap_s_'+d.id).value},'Produk disimpan');break;
    case 'adminrefresh':render();break;
    case 'logout':api('admin_logout.php',{}).catch(()=>{}).then(()=>{U.admin=false;U.adminErr='';draw()});break;
    case 'tab':U.tab=d.k;draw();break;
    case 'utab':U.atab=d.k;U.aerr={};U.af=d.k==='profil'?{nama:U.user.nama,hp:U.user.hp,alamat:U.user.alamat||''}:{};draw();break;
    case 'ulogout':doLogout();break;
    case 'loginco':U.next='checkout';go('masuk');break;
  }
});
setInterval(tick,1000);
(async()=>{
  try{const d=await api('bootstrap.php');PRODUCTS=d.products;COMBO=d.combo;CFG=d.config}catch(e){U.perr=e.msg;draw();return}
  try{const d=await api('user_me.php');if(d.user)setUser(d)}catch(e){}
  await render();
})();
})();
