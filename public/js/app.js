document.querySelector('[data-menu]')?.addEventListener('click',()=>document.querySelector('#sidebar')?.classList.toggle('open'));
document.querySelectorAll('[data-modal]').forEach(button=>button.addEventListener('click',()=>document.getElementById(button.dataset.modal)?.showModal()));
document.querySelectorAll('[data-close]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog')?.close()));
const beneficiary=document.querySelector('[data-beneficiary]');
beneficiary?.addEventListener('change',()=>{const option=beneficiary.selectedOptions[0];if(!option?.value)return;for(const [field,value] of Object.entries({recipient_name:option.dataset.name,recipient_account:option.dataset.account,recipient_bank:option.dataset.bank})){const input=document.querySelector(`[name="${field}"]`);if(input)input.value=value||'';}});
setTimeout(()=>document.querySelectorAll('.alert').forEach(a=>{a.style.opacity='0';setTimeout(()=>a.remove(),300)}),5000);

// Header notifications bell dropdown
document.querySelectorAll('[data-bell]').forEach(function(bell){
  bell.addEventListener('click',function(e){
    e.stopPropagation();
    var drop=bell.closest('.bell-wrap').querySelector('.bell-drop');
    var isHidden=drop.hidden;
    document.querySelectorAll('.bell-drop').forEach(function(d){d.hidden=true;});
    drop.hidden=!isHidden;
  });
});
document.addEventListener('click',function(e){
  if(!e.target.closest('.bell-wrap'))document.querySelectorAll('.bell-drop').forEach(function(d){d.hidden=true;});
});

// Header search — navigate on Enter
document.querySelectorAll('.search input').forEach(function(input){
  var url=input.getAttribute('data-search');
  if(!url)return;
  input.addEventListener('keydown',function(e){
    if(e.key==='Enter'){e.preventDefault();var q=input.value.trim();window.location=url+(q?'?q='+encodeURIComponent(q):'');}
  });
});
