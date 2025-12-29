const TOTP = {
  init: function () {
    this.initEvents();

    if (document.getElementById('verify-btn') != null) {
      setDefaultButton('verify-btn');

      document.querySelector('input[name="code"]').focus();
      scrollWindowTo('.code-entry', 0);
    }
    else if (document.getElementById('continue-btn')) {
      // Setup successful
      setDefaultButton('continue-btn');
      document.getElementById('continue-btn').focus();
    }
  },

  initEvents: function () {
    const self = this;
    // document.addEventListener('keydown', function (e) {
    //   if (e.key == 'Enter') {
    //     e.preventDefault();
    //     submitCode();
    //   }
    // });

    document.getElementById('verify-btn')?.addEventListener('click', function (e) {
      self.submitCode();
    });

    document.getElementById('continue-btn')?.addEventListener('click', function(e) {
        wait();
        this.disabled = true;
    });

    if (document.querySelector('.fa-copy') != null) {
      document.querySelector('.fa-copy').addEventListener('click', async function (e) {
        await self.copyToClipboard();
      });
    }
  },

  submitCode: async function () {
    const code = document.querySelector('.code-entry input[name="code"]').value;
    const errmsg = document.getElementById('errormsg');
    errmsg.style.display = 'none';
    const remember = document.getElementById('remember');
    document.getElementById('verify-btn').disabled = true;

    wait();

    params = {
      code: code,
      r: (remember !== null && remember.checked ? 1 : 0)
    };

    const data = await doFetch('post', 'totp-verify', params);

    if (data.response) {
      if (data.response === 'fail') {
        removeWait();
        errmsg.innerHTML = 'Invalid code. Please enter it again.';
        errmsg.style.display = 'block';
        document.getElementById('verify-btn').disabled = false;
        return;
      }

      redirect(data.response.next);
      return;
    }

    alert('Unexpected error');
  },

  copyToClipboard: async function() {
    const elem = document.querySelector('.secret')

    try {
      await navigator.clipboard.writeText(elem.value);
      elem.style.color = '#008000';
      document.querySelector('.fa-copy').style.color = '#008000';
      document.getElementById('copied-notify').style.display = 'block';

      setTimeout(function () {
        document.getElementById('copied-notify').style.display = 'none';
      }, 2000);

      console.log('Content copied to clipboard');
    } catch (err) {
      elem.style.color = '#800000';
      document.querySelector('.fa-copy').style.color = '#800000';
      console.error('Failed to copy: ', err);
    }
  }
}

TOTP.init();