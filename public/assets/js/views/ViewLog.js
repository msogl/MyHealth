const ViewLog = {
  init: function() {
    this.initEvents();
  },

  initEvents: function() {
    const self = this;

    document.querySelectorAll('input[name="event[]"]').forEach(function (elem) {
      elem.addEventListener('change', function(e) {
        if (!elem.checked) {
          return;
        }

        if (elem.value == 'Any') {
          document.querySelectorAll('input[name="event[]"]:not(input[value="Any"])').forEach(elem => elem.checked = false);
        }
        else if (elem.value != 'Any') {
          document.querySelector('input[name="event[]"][value="Any"]').checked = false;
        }
      })
    });

    document.getElementById('reset-btn').addEventListener('click', function(e) {
      document.querySelector('select[name="user"] option[value="Any"]').selected = true;
      document.querySelector('input[name="from"]').value = '';
      document.querySelector('input[name="to"]').value = '';
      document.querySelectorAll('input[name="event[]"]').forEach(elem => elem.checked = false);
      document.querySelector('input[name="event[]"][value="Any"]').checked = true;
      document.querySelector('input[name="limit"]').value = '200';
    });

    document.getElementById('filter-btn').addEventListener('click', function(e) {
      self.filter();
    });
  },

  filter: function() {
    document.getElementById('logs-form').submit();
  }
}

ViewLog.init();