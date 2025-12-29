const Menubar = {
  init: function() {
    this.initEvents();
  },

  initEvents: function() {
    const menuItems = document.querySelectorAll('#menubar ul li')
    menuItems.forEach(function(item) {
      item.addEventListener('click', function(e) {
        window.location.href = item.querySelector('a').getAttribute('href');
      });
    });
  }
}

Menubar.init();