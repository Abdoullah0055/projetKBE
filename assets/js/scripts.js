function toggleMenu() {
    const sidebar = document.getElementById('sidebar');
    const arrow = document.getElementById('arrow-icon');

    sidebar.classList.toggle('collapsed');
    arrow.innerHTML = sidebar.classList.contains('collapsed') ? '»' : '«';
}