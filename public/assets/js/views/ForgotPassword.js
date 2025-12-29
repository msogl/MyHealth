const ForgotPassword = {
  init: function () {
    this.initEvents();
    setDefaultButton('reset-btn');
    document.getElementById('username').focus();
  },

  initEvents: function () {
    document.getElementById('cancel-btn').addEventListener('click', function() {
      redirect('login');
    });

    document.getElementById('reset-btn').addEventListener('click', async function (e) {
      const username = document.getElementById('username');
      username.classList.remove('input-error');
      
      if (username.value == "") {
        username.classList.add('input-error');
        username.focus();
        return;
      }

      wait();
      disableElems('.button');
      await delay(50);
      document.getElementById('reset-password-form').submit();
    });
  }
}

ForgotPassword.init();
