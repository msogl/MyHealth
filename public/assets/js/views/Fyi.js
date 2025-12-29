const Fyi = {
  init: function() {
    this.initEvents();
  },

  initEvents: function() {
    const self = this;

    document.querySelectorAll('.clickable').forEach(function(elem) {
      elem.addEventListener('click', function(e) {
        self.edit(elem.dataset.id);
      })
    });
    
    document.getElementById('add-btn')?.addEventListener('click', this.add);

  },

  add: function() {
    document.edit.id.value = '';
    document.edit.submit();
  },

  edit: function(id) {
    document.edit.id.value = id;
    document.edit.submit();
  }
}

Fyi.init();