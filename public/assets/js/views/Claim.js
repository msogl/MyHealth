const Claim = {
  init: function () {
    this.initEvents();
  },

  initEvents: function () {
    const self = this;
    const rows = document.querySelectorAll('#my-claims tbody tr');

    for (let ix = 0; ix < rows.length; ix++) {
      rows[ix].addEventListener('click', function () {
        return self.view(this.dataset.id);
      });
    }
  },

  view: async function (id) {
    wait();
    await delay(50)
    document.detail_form.id.value = id;
    document.detail_form.action = 'claim-detail';
    document.detail_form.submit();
  }
}

Claim.init();
