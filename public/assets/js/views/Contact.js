const Contact = {
  init: function () {
    this.initEvents();
  },

  initEvents: function () {
    const self = this;

    document.getElementById('cancel-btn')
      .addEventListener('click', function (e) {
        history.go(-1);
      });

    document.getElementById('send-btn')
      .addEventListener('click', function (e) {
        self.send();
      });
  },

  cancel: function () {
    history.go(-1);
  },

  send: async function () {
    const errorElem = document.getElementById('error-msg');
    const sendBtn = document.getElementById('send-btn');
    const subjectElem = document.getElementById('subject');
    const messageElem = document.getElementById('message');

    errorElem.innerText = '';

    document.querySelectorAll('.input-error')
      .forEach(elem => {
        elem.classList.remove('input-error');
      })

    if (subjectElem.value == '') {
      subjectElem.classList.add('input-error');
      subjectElem.focus();
      return;
    }
    else if (messageElem.value == '') {
      messageElem.classList.add('input-error');
      messageElem.focus();
      return;
    }

    try {
      wait();
      await delay(50);
      sendBtn.disabled = true;
      const formData = new FormData();
      formData.append('subject', subjectElem.value);
      formData.append('message', messageElem.value);
      // SMELL - convert to doFetch???
      const data = await App.submitAjax('POST', formData, 'contact-send');

      removeWait();

      if (data.error) {
        if (data.error.elementId) {
          document.querySelector('#'+data.error.elementId)?.classList.add('input-error');
          return;
        }
        else {
          errorElem.innerText = data.error;
        }

        sendBtn.disabled = false;
        return;
      }

      document.querySelector('.main-inner').innerHTML = '<p>Thank you for contacting us. We will review your message and get back to you as soon as possible.</p>';
    }
    catch (e) {
      removeWait();
      console.error(e);
      return;
    }
  }
}

Contact.init();
