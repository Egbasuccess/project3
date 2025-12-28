function toggleSubmenu(id) {
            const allSubmenus = document.querySelectorAll('.submenu');
            allSubmenus.forEach(menu => {
                if (menu.id !== id) menu.classList.remove('active');
            });

            const selectedMenu = document.getElementById(id);
            if (selectedMenu) {
                selectedMenu.classList.toggle('active');
            }
        }

