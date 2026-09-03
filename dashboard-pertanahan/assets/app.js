const sidebar = document.getElementById('sidebar');
const menuButton = document.getElementById('menu-btn');
const closeButton = document.getElementById('close-btn');
const themeButton = document.getElementById('theme-btn');

if (menuButton) menuButton.addEventListener('click', () => { sidebar.style.display = 'block'; });
if (closeButton) closeButton.addEventListener('click', () => { sidebar.style.display = 'none'; });
if (themeButton) themeButton.addEventListener('click', () => {
    document.body.classList.toggle('dark');
    themeButton.querySelectorAll('span').forEach((icon) => icon.classList.toggle('active'));
});
