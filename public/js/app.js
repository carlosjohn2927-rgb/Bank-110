document.querySelector('[data-menu]')?.addEventListener('click',()=>document.querySelector('#sidebar')?.classList.toggle('open'));
document.querySelectorAll('[data-modal]').forEach(button=>button.addEventListener('click',()=>document.getElementById(button.dataset.modal)?.showModal()));
document.querySelectorAll('[data-close]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog')?.close()));
const beneficiary=document.querySelector('[data-beneficiary]');
beneficiary?.addEventListener('change',()=>{const option=beneficiary.selectedOptions[0];if(!option?.value)return;for(const [field,value] of Object.entries({recipient_name:option.dataset.name,recipient_account:option.dataset.account,recipient_bank:option.dataset.bank})){const input=document.querySelector(`[name="${field}"]`);if(input)input.value=value||'';}});
setTimeout(()=>document.querySelectorAll('.alert').forEach(a=>{a.style.opacity='0';setTimeout(()=>a.remove(),300)}),5000);
