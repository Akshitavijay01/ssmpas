// Sidebar toggle for mobile + active state
(function(){
    var btn = document.getElementById('sidebarToggle');
    if(!btn) return;
    btn.addEventListener('click', function(){
        var sidebars = document.querySelectorAll('.sidebar');
        sidebars.forEach(function(sb){ sb.classList.toggle('show'); });
    });
})();
