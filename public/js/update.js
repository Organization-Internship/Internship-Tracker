const updateForm = document.getElementById('updateForm');
if (updateForm) {
  updateForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(updateForm);
    try {
      const res = await fetch('/php/auth/users/update_profile.php', {
        method: 'POST',
        body: fd,
        credentials: 'include'
      });
      const data = await res.json();
      document.getElementById('msg').textContent = data.message;
    } catch (err) {
      console.error(err);
      document.getElementById('msg').textContent = 'Error updating profile';
    }
  });
}
