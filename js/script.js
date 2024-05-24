
document.addEventListener('DOMContentLoaded', function() {
  const registerButton = document.getElementById('register-button');
  if (registerButton) {
    registerButton.addEventListener('click', function() {
      window.location.href = 'register.php';  
    });
  }
});
