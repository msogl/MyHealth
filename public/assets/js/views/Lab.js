const Lab = {
  init: function () {
    this.initEvents();
  },

  initEvents: function () {
    const self = this;
    const rows = document.querySelectorAll('#my-labs tbody tr');

    for (let ix = 0; ix < rows.length; ix++) {
      rows[ix].addEventListener('click', function () {
        return self.view(this.dataset.id, this.dataset.order);
      });
    }
  },

  view: function (id, order) {
    document.detail_form.id.value = id;
    document.detail_form.order.value = order
    document.detail_form.action = 'lab-detail';
    document.detail_form.submit();
  }
}

Lab.init();
