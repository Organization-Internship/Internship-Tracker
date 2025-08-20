async function postForm(url, form) {
  const fd = new FormData(form);
  const res = await fetch(url, { method: 'POST', body: fd, credentials: 'include' });
  return res.json();
}
const msg = (t)=>{ const el=document.getElementById('msg'); if(el) el.textContent=t; };

const loginForm = document.getElementById('loginForm');
if (loginForm) loginForm.addEventListener('submit', async (e)=>{
  e.preventDefault();
  try{
    const data = await postForm('../php/auth/login.php', loginForm);
    if (data.status==='success'){
      if (data.role==='student') location.href='../student/dashboard.html';
      else if (data.role==='faculty') location.href='../faculty/dashboard.html';
      else if (data.role==='company') location.href='../company/dashboard.html';
      else msg('Unknown role');
    } else msg(data.message||'Login failed');
  } catch(err){ msg('Network error'); }
});

const registerForm = document.getElementById('registerForm');
if (registerForm) registerForm.addEventListener('submit', async (e)=>{
  e.preventDefault();
  try{
    const data = await postForm('../php/auth/register.php', registerForm);
    msg(data.message||'Done');
    if (data.status==='success') location.href='../public/login.html';
  } catch(err){ msg('Network error'); }
});

const forgotForm = document.getElementById('forgotForm');
if (forgotForm) forgotForm.addEventListener('submit', async (e)=>{
  e.preventDefault();
  try{ const d=await postForm('../php/auth/forgot_password.php', forgotForm); msg(d.message||'Link created (demo)'); }catch(err){ msg('Network error'); }
});
const resetForm = document.getElementById('resetForm');
if (resetForm) resetForm.addEventListener('submit', async (e)=>{
  e.preventDefault();
  try{ const d=await postForm('../php/auth/reset_password.php', resetForm); msg(d.message||'Password reset'); if(d.status==='success') location.href='../public/login.html'; }catch(err){ msg('Network error'); }
});
