const Referral = {
  token: '',
  
  init: function () {
    this.initEvents();
  },

  initEvents: function () {
    const self = this;
    const rows = document.querySelectorAll('#my-referrals tbody tr');

    for (let ix = 0; ix < rows.length; ix++) {
      rows[ix].addEventListener('click', function () {
        return self.view(this.dataset.id);
      });
    }
  },

  view: async function (id) {
    wait();
    await delay(50);
    document.detail_form.id.value = id;
    document.detail_form.action = "referral-detail";
    document.detail_form.submit();
  },

  reprint: function (referralNumber) {
    // take up 70% of the screen width and 60% of the screen height
    let width = screen.width * .7;
    let height = screen.height * .6;

    // but not less than 600 x 500
    if (width < 600) {
      width = 600;
    }

    openwinpost(`reprint?refnum=${referralNumber}&token=${App.CSRFToken}`, width, height, true);
  }
}

Referral.init();