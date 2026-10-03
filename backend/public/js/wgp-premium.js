document.addEventListener('DOMContentLoaded',()=>{
 document.body.classList.add('wgp-page-enter');
 const reduce=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
 const reveal=[...document.querySelectorAll('.dashboard-module-section,.wgp-panel,.wgp-table-wrap,.dashboard-welcome')];
 reveal.forEach((el,i)=>{el.dataset.wgpReveal=''; el.style.transitionDelay=reduce?'0ms':`${Math.min(i*45,360)}ms`; requestAnimationFrame(()=>el.classList.add('wgp-visible'));});
 const menu=document.querySelector('.wgp-mobile-menu'); const sidebar=document.querySelector('.dashboard-sidebar');
 menu?.addEventListener('click',()=>{const open=sidebar?.classList.toggle('wgp-mobile-open'); menu.setAttribute('aria-expanded',open?'true':'false');});
 document.querySelectorAll('.dashboard-primary-action,.wgp-gold-btn,.btn-primary').forEach(btn=>{
  btn.addEventListener('click',e=>{if(reduce)return; const r=btn.getBoundingClientRect(); const size=Math.max(r.width,r.height); const s=document.createElement('span'); s.className='wgp-ripple'; s.style.width=s.style.height=size+'px'; s.style.left=(e.clientX-r.left-size/2)+'px'; s.style.top=(e.clientY-r.top-size/2)+'px'; btn.style.position='relative'; btn.style.overflow='hidden'; btn.appendChild(s); setTimeout(()=>s.remove(),600);});
 });
 document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',()=>{const b=form.querySelector('button[type="submit"]'); if(!b||b.dataset.loading)return; b.dataset.loading='1'; b.dataset.originalText=b.innerHTML; b.innerHTML='<span class="wgp-btn-spinner" aria-hidden="true"></span> Working...'; b.disabled=true;}));
 document.querySelectorAll('[data-counter]').forEach(el=>{const target=Number(el.dataset.counter||0); if(!Number.isFinite(target)||reduce){el.textContent=target.toLocaleString();return;} let start=0; const duration=800; const t0=performance.now(); const tick=t=>{const p=Math.min(1,(t-t0)/duration); const eased=1-Math.pow(1-p,3); el.textContent=Math.round(start+(target-start)*eased).toLocaleString(); if(p<1)requestAnimationFrame(tick)}; requestAnimationFrame(tick);});
 document.querySelectorAll('a[href]').forEach(a=>a.addEventListener('click',e=>{if(reduce||e.metaKey||e.ctrlKey||e.shiftKey||e.altKey||a.target==='_blank')return; const href=a.getAttribute('href'); if(!href||href.startsWith('#')||href.startsWith('javascript:')||href.startsWith('mailto:'))return; try{if(new URL(href,location.href).origin!==location.origin)return;}catch{return;} document.body.classList.add('wgp-navigating');}));
});

const imageInput=document.querySelector('[data-image-preview-input]');
const imagePreview=document.querySelector('[data-image-preview]');
imageInput?.addEventListener('change',()=>{const file=imageInput.files?.[0];if(!file||!imagePreview)return;const url=URL.createObjectURL(file);imagePreview.innerHTML=`<img src="${url}" alt="Product preview">`;imagePreview.animate([{transform:'scale(.96)',opacity:.5},{transform:'scale(1)',opacity:1}],{duration:260,easing:'cubic-bezier(.2,.8,.2,1)'});});
