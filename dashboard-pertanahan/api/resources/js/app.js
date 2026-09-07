import './bootstrap';
const sidebar=document.getElementById('sidebar');
document.getElementById('open-sidebar')?.addEventListener('click',()=>sidebar?.classList.add('open'));
document.getElementById('close-sidebar')?.addEventListener('click',()=>sidebar?.classList.remove('open'));
document.getElementById('theme-toggle')?.addEventListener('click',()=>document.body.classList.toggle('dark-mode'));

for (const form of document.querySelectorAll('[data-submit-state]')) { form.addEventListener('submit', (event) => { if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) { event.preventDefault(); return; } if (!form.checkValidity()) return; const button=form.querySelector('button[type=submit]'); if (button) { button.disabled=true; button.textContent='Memproses...'; } }); } document.querySelectorAll('[data-reject-form]').forEach(form=>form.addEventListener('submit',e=>{ const input=form.querySelector('input[name=reason]'); if(input && !input.value.trim()){e.preventDefault(); input.focus();} }));
