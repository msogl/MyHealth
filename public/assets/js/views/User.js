const User = {
  init: function () {
    this.initEvents();
    document.getElementById('filter').focus();
  },

  initEvents: function () {
    const self = this;

    document.querySelectorAll('.clickable').forEach(function (elem) {
      elem.addEventListener('click', function (e) {
        const editForm = document.getElementById('edit-form');
        editForm.querySelector('input[name="id"]').value = elem.dataset.id;
        editForm.submit();
      });
    });

    document.getElementById('filter').addEventListener('keyup', function (e) {
      self.filter();
    });
  },

  filter: function () {
    const filterElem = document.getElementById('filter');
    const filter = filterElem.value.toLowerCase();

    if (filter.trim() == '') {
      document.querySelectorAll('.filterable tbody tr').forEach(elem => {
        elem.classList.remove('odd', 'even');
        elem.style.display = 'table-row';
      });
      return;
    }

    let ix = 0;
    document.querySelectorAll('.filterable tbody tr')?.forEach(function (row) {
      const username = row.querySelector('.username').textContent;
      const email = row.querySelector('.email').textContent;
      const nickname = row.querySelector('.nickname').textContent;
      const memid = row.querySelector('.memid').textContent;

      row.classList.remove('odd', 'even');

      if ((username.toLowerCase().includes(filter)) ||
        (email.toLowerCase().includes(filter)) ||
        (nickname.toLowerCase().includes(filter)) ||
        (memid.toLowerCase().includes(filter))) {
        row.classList.add((ix % 2 == 0) ? 'odd' : 'even');
        row.style.display = '';
        ix++;
      }
      else {
        row.style.display = 'none';
      }
    });

    filterElem.focus();
  }
}

User.init();