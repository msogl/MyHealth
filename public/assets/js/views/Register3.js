const Register3 = {
  init: function() {
    this.initEvents();
    setDefaultButton('confirm-btn');
  },

  initEvents: function() {
    const self = this;

    document.getElementById('confirm-btn').addEventListener('click', function(e) {
      self.confirm();
    });

    document.getElementById('prev-btn')?.addEventListener('click', function(e) {
      console.log('prev');
      history.go(-1);
    });
  },

  confirm: async function() {
    TrackMessage.clearMessage();
    
    wait();
    disableElems('.button')

    const data = await doFetch('post', 'save-registration', { meta: App.meta })
    removeWait();

    if (data.error) {
      TrackMessage.showMessage('error', data.error);
      enableElems('.button');
    }

    if (data.response.next) {
      redirect(`register-complete?safe=${data.response.safe}`);
    }
  },
}

Register3.init();